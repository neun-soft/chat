<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    Hash,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ChannelFormDialog from '@/components/admin/ChannelFormDialog.vue';
import ChannelMembersDialog from '@/components/admin/ChannelMembersDialog.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import WorkspaceFormDialog from '@/components/admin/WorkspaceFormDialog.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type AdminUser = { id: number; name: string; email: string; is_admin: boolean };
type AdminChannel = {
    id: number;
    name: string;
    slug: string;
    topic: string | null;
    member_ids: number[];
};
type Member = { id: number; name: string; email: string; role: string };
type AdminWorkspace = {
    id: number;
    name: string;
    slug: string;
    archived: boolean;
    members: Member[];
    channels: AdminChannel[];
};

const props = defineProps<{
    workspace: AdminWorkspace;
    users: AdminUser[];
}>();

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

const nonMembers = computed(() => {
    const ids = new Set(props.workspace.members.map((m) => m.id));
    return props.users.filter((u) => !ids.has(u.id));
});

// --- Members -----------------------------------------------------------
const memberSelect = ref('');
function addMember() {
    if (!memberSelect.value) {
        return;
    }
    router.post(
        `/admin/workspaces/${props.workspace.id}/members`,
        { user_id: memberSelect.value, role: 'member' },
        { preserveScroll: true, onSuccess: () => (memberSelect.value = '') },
    );
}

const memberToRemove = ref<Member | null>(null);
const confirmRemoveMember = ref(false);
function askRemoveMember(member: Member) {
    memberToRemove.value = member;
    confirmRemoveMember.value = true;
}
function removeMember() {
    if (!memberToRemove.value) {
        return;
    }
    router.delete(
        `/admin/workspaces/${props.workspace.id}/members/${memberToRemove.value.id}`,
        {
            preserveScroll: true,
        },
    );
}

// --- Workspace actions -------------------------------------------------
const renameOpen = ref(false);
function toggleArchive() {
    router.patch(
        `/admin/workspaces/${props.workspace.id}`,
        { archived: !props.workspace.archived },
        { preserveScroll: true },
    );
}

// --- Channels ----------------------------------------------------------
const createChannelOpen = ref(false);

const channelToEdit = ref<AdminChannel | null>(null);
const editChannelOpen = ref(false);
function openEditChannel(channel: AdminChannel) {
    channelToEdit.value = channel;
    editChannelOpen.value = true;
}

const channelForMembers = ref<AdminChannel | null>(null);
const membersOpen = ref(false);
function openChannelMembers(channel: AdminChannel) {
    channelForMembers.value = channel;
    membersOpen.value = true;
}

const channelToDelete = ref<AdminChannel | null>(null);
const confirmDeleteChannel = ref(false);
function askDeleteChannel(channel: AdminChannel) {
    channelToDelete.value = channel;
    confirmDeleteChannel.value = true;
}
function deleteChannel() {
    if (!channelToDelete.value) {
        return;
    }
    router.delete(`/admin/channels/${channelToDelete.value.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Card :class="workspace.archived ? 'opacity-70' : ''">
        <CardHeader class="flex flex-row items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="truncate text-lg font-semibold tracking-tight">
                        {{ workspace.name }}
                    </h3>
                    <Badge v-if="workspace.archived" variant="secondary"
                        >Archived</Badge
                    >
                </div>
                <div
                    class="mt-1 flex items-center gap-3 text-xs text-muted-foreground"
                >
                    <code>/{{ workspace.slug }}</code>
                    <span class="flex items-center gap-1"
                        ><Users class="size-3" />
                        {{ workspace.members.length }}</span
                    >
                    <span class="flex items-center gap-1"
                        ><Hash class="size-3" />
                        {{ workspace.channels.length }}</span
                    >
                </div>
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Workspace actions"
                    >
                        <MoreHorizontal />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="renameOpen = true">
                        <Pencil /> Rename
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="toggleArchive">
                        <component
                            :is="workspace.archived ? ArchiveRestore : Archive"
                        />
                        {{ workspace.archived ? 'Unarchive' : 'Archive' }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </CardHeader>

        <CardContent class="grid gap-6 md:grid-cols-2">
            <!-- Members -->
            <div>
                <h4
                    class="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Members
                </h4>

                <ul v-if="workspace.members.length" class="mb-3 space-y-1">
                    <li
                        v-for="m in workspace.members"
                        :key="m.id"
                        class="group flex items-center gap-2 rounded-md px-1 py-1 hover:bg-muted"
                    >
                        <Avatar class="size-7">
                            <AvatarFallback class="text-xs">{{
                                initials(m.name)
                            }}</AvatarFallback>
                        </Avatar>
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-sm">{{ m.name }}</span>
                            <span
                                class="truncate text-xs text-muted-foreground"
                                >{{ m.role }}</span
                            >
                        </span>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-muted-foreground opacity-0 group-hover:opacity-100 hover:text-destructive"
                            aria-label="Remove member"
                            @click="askRemoveMember(m)"
                        >
                            <X />
                        </Button>
                    </li>
                </ul>
                <p v-else class="mb-3 text-sm text-muted-foreground">
                    No members yet.
                </p>

                <div class="flex gap-2">
                    <select
                        v-model="memberSelect"
                        class="h-9 flex-1 rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="">Add member…</option>
                        <option
                            v-for="u in nonMembers"
                            :key="u.id"
                            :value="u.id"
                        >
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </select>
                    <Button
                        variant="outline"
                        size="icon"
                        :disabled="!memberSelect"
                        aria-label="Add member"
                        @click="addMember"
                    >
                        <UserPlus />
                    </Button>
                </div>
            </div>

            <!-- Channels -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h4
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Channels
                    </h4>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="createChannelOpen = true"
                    >
                        <Plus /> New
                    </Button>
                </div>

                <ul v-if="workspace.channels.length" class="space-y-2">
                    <li
                        v-for="channel in workspace.channels"
                        :key="channel.id"
                        class="rounded-md border border-border p-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <span
                                    class="flex items-center gap-1 font-medium"
                                >
                                    <Hash
                                        class="size-3.5 text-muted-foreground"
                                    />{{ channel.slug }}
                                </span>
                                <p
                                    v-if="channel.topic"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ channel.topic }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Edit channel"
                                    @click="openEditChannel(channel)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-muted-foreground hover:text-destructive"
                                    aria-label="Delete channel"
                                    @click="askDeleteChannel(channel)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="mt-2 flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                            @click="openChannelMembers(channel)"
                        >
                            <Users class="size-3" />
                            {{ channel.member_ids.length }}
                            {{
                                channel.member_ids.length === 1
                                    ? 'member'
                                    : 'members'
                            }}
                            · manage
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No channels yet.
                </p>
            </div>
        </CardContent>
    </Card>

    <!-- Dialogs -->
    <WorkspaceFormDialog
        v-model:open="renameOpen"
        mode="edit"
        :workspace="workspace"
    />

    <ChannelFormDialog
        v-model:open="createChannelOpen"
        mode="create"
        :workspace-id="workspace.id"
        :member-ids="workspace.members.map((m) => m.id)"
    />

    <ChannelFormDialog
        v-if="channelToEdit"
        v-model:open="editChannelOpen"
        mode="edit"
        :workspace-id="workspace.id"
        :channel="channelToEdit"
    />

    <ChannelMembersDialog
        v-if="channelForMembers"
        v-model:open="membersOpen"
        :channel="channelForMembers"
        :members="workspace.members"
    />

    <ConfirmDialog
        v-model:open="confirmRemoveMember"
        title="Remove member?"
        :description="`${memberToRemove?.name} will be removed from ${workspace.name} and all its channels.`"
        confirm-label="Remove"
        @confirm="removeMember"
    />

    <ConfirmDialog
        v-model:open="confirmDeleteChannel"
        title="Delete channel?"
        :description="`#${channelToDelete?.slug} and all of its messages will be permanently deleted.`"
        confirm-label="Delete"
        @confirm="deleteChannel"
    />
</template>
