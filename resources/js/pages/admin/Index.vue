<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Hash,
    LayoutGrid,
    Plus,
    Search,
    Shield,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import UserFormDialog from '@/components/admin/UserFormDialog.vue';
import WorkspaceCard from '@/components/admin/WorkspaceCard.vue';
import WorkspaceFormDialog from '@/components/admin/WorkspaceFormDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type AdminUser = { id: number; name: string; email: string; is_admin: boolean };
type AdminChannel = {
    id: number;
    name: string;
    slug: string;
    topic: string | null;
    member_ids: number[];
};
type AdminWorkspace = {
    id: number;
    name: string;
    slug: string;
    archived: boolean;
    members: { id: number; name: string; email: string; role: string }[];
    channels: AdminChannel[];
};

const props = defineProps<{
    workspaces: AdminWorkspace[];
    users: AdminUser[];
}>();

const page = usePage();
const flash = computed(
    () => (page.props.flash as { success?: string })?.success,
);

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

const channelCount = computed(() =>
    props.workspaces.reduce((sum, w) => sum + w.channels.length, 0),
);

const membershipCount = computed(() => {
    const counts = new Map<number, number>();
    for (const w of props.workspaces) {
        for (const m of w.members) {
            counts.set(m.id, (counts.get(m.id) ?? 0) + 1);
        }
    }
    return counts;
});

// --- Search ------------------------------------------------------------
const search = ref('');
const showArchived = ref(true);

const filteredUsers = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return props.users;
    }
    return props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q),
    );
});

const filteredWorkspaces = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.workspaces.filter((w) => {
        if (!showArchived.value && w.archived) {
            return false;
        }
        if (!q) {
            return true;
        }
        return (
            w.name.toLowerCase().includes(q) ||
            w.slug.toLowerCase().includes(q) ||
            w.members.some((m) => m.name.toLowerCase().includes(q))
        );
    });
});

// --- Dialogs -----------------------------------------------------------
const createUserOpen = ref(false);
const createWorkspaceOpen = ref(false);

const userToEdit = ref<AdminUser | null>(null);
const editUserOpen = ref(false);
function openEditUser(user: AdminUser) {
    userToEdit.value = user;
    editUserOpen.value = true;
}
</script>

<template>
    <Head title="Admin" />

    <div
        class="mx-auto min-h-screen max-w-5xl bg-background px-6 py-10 text-foreground"
    >
        <!-- Header -->
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Admin console
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage logins, workspaces, and channels.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="/chat"
                    class="text-sm text-muted-foreground hover:text-foreground"
                    >← Back to chat</a
                >
                <Button variant="outline" @click="createUserOpen = true"
                    ><Plus /> New login</Button
                >
                <Button @click="createWorkspaceOpen = true"
                    ><Plus /> New workspace</Button
                >
            </div>
        </header>

        <!-- Flash -->
        <p
            v-if="flash"
            class="mb-6 flex items-center gap-2 rounded-md border border-green-600/30 bg-green-600/10 px-4 py-2 text-sm text-green-700 dark:text-green-400"
        >
            <CheckCircle2 class="size-4" /> {{ flash }}
        </p>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-3 gap-3">
            <div class="rounded-lg border border-border p-4">
                <div
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <Users class="size-3.5" /> Logins
                </div>
                <p class="mt-1 text-2xl font-semibold">{{ users.length }}</p>
            </div>
            <div class="rounded-lg border border-border p-4">
                <div
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <LayoutGrid class="size-3.5" /> Workspaces
                </div>
                <p class="mt-1 text-2xl font-semibold">
                    {{ workspaces.length }}
                </p>
            </div>
            <div class="rounded-lg border border-border p-4">
                <div
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <Hash class="size-3.5" /> Channels
                </div>
                <p class="mt-1 text-2xl font-semibold">{{ channelCount }}</p>
            </div>
        </div>

        <!-- Search -->
        <div class="relative mb-6">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                placeholder="Search people and workspaces…"
                class="pl-9"
            />
        </div>

        <!-- People -->
        <section class="mb-10">
            <h2
                class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                People
            </h2>
            <div class="overflow-hidden rounded-lg border border-border">
                <button
                    v-for="user in filteredUsers"
                    :key="user.id"
                    type="button"
                    class="flex w-full items-center gap-3 border-b border-border px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-muted"
                    @click="openEditUser(user)"
                >
                    <Avatar class="size-9">
                        <AvatarFallback class="text-xs">{{
                            initials(user.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 truncate font-medium">
                            {{ user.name }}
                            <Badge
                                v-if="user.is_admin"
                                variant="secondary"
                                class="gap-1"
                                ><Shield class="size-3" /> Admin</Badge
                            >
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-muted-foreground">
                        {{ membershipCount.get(user.id) ?? 0 }}
                        {{
                            (membershipCount.get(user.id) ?? 0) === 1
                                ? 'workspace'
                                : 'workspaces'
                        }}
                    </span>
                </button>
                <p
                    v-if="filteredUsers.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No people match “{{ search }}”.
                </p>
            </div>
        </section>

        <!-- Workspaces -->
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2
                    class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Workspaces
                </h2>
                <label
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <input
                        v-model="showArchived"
                        type="checkbox"
                        class="rounded border-input"
                    />
                    Show archived
                </label>
            </div>

            <div v-if="filteredWorkspaces.length" class="space-y-6">
                <WorkspaceCard
                    v-for="workspace in filteredWorkspaces"
                    :key="workspace.id"
                    :workspace="workspace"
                    :users="users"
                />
            </div>

            <div
                v-else
                class="rounded-lg border border-dashed border-border py-12 text-center"
            >
                <LayoutGrid class="mx-auto mb-3 size-8 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                    {{
                        workspaces.length === 0
                            ? 'No workspaces yet.'
                            : 'No workspaces match your search.'
                    }}
                </p>
                <Button
                    v-if="workspaces.length === 0"
                    class="mt-4"
                    @click="createWorkspaceOpen = true"
                >
                    <Plus /> Create your first workspace
                </Button>
            </div>
        </section>
    </div>

    <!-- Global dialogs -->
    <UserFormDialog v-model:open="createUserOpen" mode="create" />
    <UserFormDialog
        v-if="userToEdit"
        v-model:open="editUserOpen"
        mode="edit"
        :user="userToEdit"
    />
    <WorkspaceFormDialog v-model:open="createWorkspaceOpen" mode="create" />
</template>
