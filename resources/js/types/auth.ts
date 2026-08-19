import type { Permission } from './permission';

export type User = {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    avatar?: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: Record<Permission, boolean>;
};
