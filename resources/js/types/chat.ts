export type ChatUser = {
    id: number;
    name: string;
};

export type ChatMessage = {
    id: number;
    channel_id: number;
    body: string;
    image_url: string | null;
    created_at: string;
    user: ChatUser;
};

export type ChatChannel = {
    id: number;
    name: string;
    slug: string;
    topic: string | null;
};

export type ChatWorkspace = {
    id: number;
    name: string;
    slug: string;
    channels: ChatChannel[];
};
