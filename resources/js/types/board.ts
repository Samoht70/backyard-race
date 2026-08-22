export type Board = {
    name: string;
    status: string;
    confirmed_participants: number;
    max_participants: number | null;
    first_start_time: string | null;
    first_start_day: string | null;
};
