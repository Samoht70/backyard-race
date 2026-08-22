<?php

namespace App\Http\Controllers\Manage;

use App\Actions\DeleteRegistration;
use App\Actions\UpdateRegistration;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\RegistrationDestroyRequest;
use App\Http\Requests\Manage\RegistrationIndexRequest;
use App\Http\Requests\Manage\RegistrationUpdateRequest;
use App\Http\Resources\Manage\RegistrationResource;
use App\Models\Event;
use App\Models\Participant;
use App\Support\RegistrationDeletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(RegistrationIndexRequest $request): Response
    {
        $event = Event::query()->firstOrNew();
        $status = $request->status();

        return Inertia::render('manage/registrations/Index', [
            'registrations' => RegistrationResource::collection($this->registrations($event, $status))->resolve(),
            'counts' => $this->counts($event),
            'seats' => [
                'confirmed' => $event->confirmedParticipantsCount(),
                'capacity' => $event->max_participants,
            ],
            'status' => $status?->value,
            'refusals' => $event->isFull() ? [__('registration.refusal.full')] : [],
            'deletionRefusal' => RegistrationDeletion::refusal($event),
        ]);
    }

    public function edit(Participant $participant): Response
    {
        Gate::authorize('manage', $participant);

        return Inertia::render('manage/registrations/Edit', [
            'registration' => new RegistrationResource($participant)->resolve(),
        ]);
    }

    public function update(
        RegistrationUpdateRequest $request,
        UpdateRegistration $update,
        Participant $participant,
    ): RedirectResponse {
        $update($participant, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('registration.manage.saved')]);

        return to_route('manage.registrations.edit', $participant);
    }

    public function destroy(
        RegistrationDestroyRequest $request,
        DeleteRegistration $delete,
        Participant $participant,
    ): RedirectResponse {
        $runner = $participant->user->name;

        $delete($participant);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('registration.manage.deleted', ['name' => $runner]),
        ]);

        return to_route('manage.registrations.index');
    }

    /**
     * @return Collection<int, Participant>
     */
    private function registrations(Event $event, ?RegistrationStatus $status): Collection
    {
        return $event->participants()
            ->with('user')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->when($status !== null, fn (Builder $query): Builder => $query->where('participants.status', $status))
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->select('participants.*')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    private function counts(Event $event): array
    {
        $tally = $event->participants()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['all' => (int) $tally->sum()];

        foreach (RegistrationStatus::cases() as $status) {
            $counts[$status->value] = (int) ($tally[$status->value] ?? 0);
        }

        return $counts;
    }
}
