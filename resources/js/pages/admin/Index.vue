<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
const flash = computed(() => (page.props.flash as { success?: string })?.success);

// --- Create a client login ---------------------------------------------
const userForm = useForm({ name: '', email: '', password: '', is_admin: false });
function createUser() {
    userForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => userForm.reset(),
    });
}

// --- Create a workspace -------------------------------------------------
const workspaceForm = useForm({ name: '' });
function createWorkspace() {
    workspaceForm.post('/admin/workspaces', {
        preserveScroll: true,
        onSuccess: () => workspaceForm.reset(),
    });
}

// --- Per-workspace member add ------------------------------------------
const memberSelect = ref<Record<number, string>>({});
function addMember(workspace: AdminWorkspace) {
    const userId = memberSelect.value[workspace.id];
    if (!userId) return;
    router.post(
        `/admin/workspaces/${workspace.id}/members`,
        { user_id: userId, role: 'member' },
        { preserveScroll: true, onSuccess: () => (memberSelect.value[workspace.id] = '') },
    );
}
function removeMember(workspace: AdminWorkspace, userId: number) {
    router.delete(`/admin/workspaces/${workspace.id}/members/${userId}`, {
        preserveScroll: true,
    });
}

function nonMembers(workspace: AdminWorkspace): AdminUser[] {
    const ids = new Set(workspace.members.map((m) => m.id));
    return props.users.filter((u) => !ids.has(u.id));
}

// --- Per-workspace channel create --------------------------------------
const channelDraft = ref<Record<number, { name: string; topic: string }>>({});
function draftFor(id: number) {
    if (!channelDraft.value[id]) channelDraft.value[id] = { name: '', topic: '' };
    return channelDraft.value[id];
}
function createChannel(workspace: AdminWorkspace) {
    const draft = draftFor(workspace.id);
    if (!draft.name.trim()) return;
    router.post(
        `/admin/workspaces/${workspace.id}/channels`,
        // New channel includes every current workspace member by default.
        { name: draft.name, topic: draft.topic, member_ids: workspace.members.map((m) => m.id) },
        { preserveScroll: true, onSuccess: () => (channelDraft.value[workspace.id] = { name: '', topic: '' }) },
    );
}
function deleteChannel(channel: AdminChannel) {
    if (!confirm(`Delete #${channel.slug}? This removes its messages.`)) return;
    router.delete(`/admin/channels/${channel.id}`, { preserveScroll: true });
}

// Toggle a member in/out of a channel.
function toggleChannelMember(workspace: AdminWorkspace, channel: AdminChannel, userId: number) {
    const set = new Set(channel.member_ids);
    set.has(userId) ? set.delete(userId) : set.add(userId);
    router.post(
        `/admin/channels/${channel.id}/members`,
        { member_ids: [...set] },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Admin" />

    <div class="mx-auto min-h-screen max-w-5xl bg-background px-6 py-10 text-foreground">
        <header class="mb-8 flex items-center justify-between">
            <h1 class="text-2xl font-semibold tracking-tight">Admin console</h1>
            <a href="/chat" class="text-sm text-muted-foreground hover:text-foreground">← Back to chat</a>
        </header>

        <p
            v-if="flash"
            class="mb-6 rounded-md border border-green-600/30 bg-green-600/10 px-4 py-2 text-sm text-green-700 dark:text-green-400"
        >
            {{ flash }}
        </p>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Create login -->
            <section class="rounded-lg border border-border p-5">
                <h2 class="mb-3 font-semibold">Create a login</h2>
                <form class="space-y-2" @submit.prevent="createUser">
                    <input v-model="userForm.name" placeholder="Name" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                    <input v-model="userForm.email" type="email" placeholder="Email" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                    <input v-model="userForm.password" type="text" placeholder="Password" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                    <label class="flex items-center gap-2 text-sm text-muted-foreground">
                        <input v-model="userForm.is_admin" type="checkbox" /> Admin (full access)
                    </label>
                    <button type="submit" :disabled="userForm.processing" class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50">
                        Create login
                    </button>
                </form>
            </section>

            <!-- Create workspace -->
            <section class="rounded-lg border border-border p-5">
                <h2 class="mb-3 font-semibold">Create a workspace</h2>
                <form class="space-y-2" @submit.prevent="createWorkspace">
                    <input v-model="workspaceForm.name" placeholder="Project name" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm" />
                    <button type="submit" :disabled="workspaceForm.processing" class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50">
                        Create workspace
                    </button>
                </form>
                <p class="mt-3 text-xs text-muted-foreground">{{ users.length }} logins · {{ workspaces.length }} workspaces</p>
            </section>
        </div>

        <!-- Workspaces -->
        <section class="mt-10 space-y-6">
            <article v-for="workspace in workspaces" :key="workspace.id" class="rounded-lg border border-border p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold tracking-tight">{{ workspace.name }}</h3>
                    <code class="text-xs text-muted-foreground">/{{ workspace.slug }}</code>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Members -->
                    <div>
                        <h4 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Members</h4>
                        <ul class="mb-3 space-y-1">
                            <li v-for="m in workspace.members" :key="m.id" class="flex items-center justify-between text-sm">
                                <span>{{ m.name }} <span class="text-muted-foreground">· {{ m.role }}</span></span>
                                <button class="text-xs text-muted-foreground hover:text-destructive" @click="removeMember(workspace, m.id)">remove</button>
                            </li>
                        </ul>
                        <div class="flex gap-2">
                            <select v-model="memberSelect[workspace.id]" class="flex-1 rounded-md border border-border bg-background px-2 py-1.5 text-sm">
                                <option value="">Add member…</option>
                                <option v-for="u in nonMembers(workspace)" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                            </select>
                            <button class="rounded-md border border-border px-3 text-sm" @click="addMember(workspace)">Add</button>
                        </div>
                    </div>

                    <!-- Channels -->
                    <div>
                        <h4 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Channels</h4>
                        <ul class="mb-3 space-y-3">
                            <li v-for="channel in workspace.channels" :key="channel.id" class="rounded-md border border-border p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">#{{ channel.slug }}</span>
                                    <button class="text-xs text-muted-foreground hover:text-destructive" @click="deleteChannel(channel)">delete</button>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <button
                                        v-for="m in workspace.members"
                                        :key="m.id"
                                        type="button"
                                        class="rounded-full border px-2 py-0.5 text-xs transition-colors"
                                        :class="channel.member_ids.includes(m.id) ? 'border-primary bg-primary text-primary-foreground' : 'border-border text-muted-foreground'"
                                        @click="toggleChannelMember(workspace, channel, m.id)"
                                    >
                                        {{ m.name }}
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <form class="space-y-2" @submit.prevent="createChannel(workspace)">
                            <input v-model="draftFor(workspace.id).name" placeholder="new-channel" class="w-full rounded-md border border-border bg-background px-3 py-1.5 text-sm" />
                            <div class="flex gap-2">
                                <input v-model="draftFor(workspace.id).topic" placeholder="Topic (optional)" class="flex-1 rounded-md border border-border bg-background px-3 py-1.5 text-sm" />
                                <button type="submit" class="rounded-md border border-border px-3 text-sm">Add channel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
