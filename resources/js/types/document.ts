export type EventDocument = {
    id: number;
    title: string;
    description: string | null;
    file_name: string | null;
    extension: string | null;
    size: number | null;
    url: string | null;
};
