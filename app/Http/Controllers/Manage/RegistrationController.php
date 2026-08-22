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
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public const PER_PAGE = 10;

    public function index(RegistrationIndexRequest $request): Response|RedirectResponse
    {
        $event = Event::currentOrNew();
        $status = $request->status();
        $registrations = $this->registrations($event, $status);

        if ($registrations->currentPage() > $registrations->lastPage()) {
            return $this->backToTheLastPage($registrations->lastPage(), $status);
        }

        return Inertia::render('manage/registrations/Index', [
            'registrations' => RegistrationResource::collection($registrations)->resolve(),
            'pagination' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
            ],
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

        $this->flashSuccess(__('registration.manage.saved'));

        return to_route('manage.registrations.edit', $participant);
    }

    public function destroy(
        RegistrationDestroyRequest $request,
        DeleteRegistration $delete,
        Participant $participant,
    ): RedirectResponse {
        $runner = $participant->user->name;

        $delete($participant);

        $this->flashSuccess(__('registration.manage.deleted', ['name' => $runner]));

        return to_route('manage.registrations.index');
    }

    /**
     * @return LengthAwarePaginator<int, Participant>
     */
    private function registrations(Event $event, ?RegistrationStatus $status): LengthAwarePaginator
    {
        return $event->participants()
            ->with('user')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->when($status !== null, fn (Builder $query): Builder => $query->where('participants.status', $status))
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->select('participants.*')
            ->paginate(self::PER_PAGE);
    }

    private function backToTheLastPage(int $lastPage, ?RegistrationStatus $status): RedirectResponse
    {
        $query = $status === null ? [] : ['status' => $status->value];
        $query['page'] = $lastPage;

        return to_route('manage.registrations.index', $query);
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
