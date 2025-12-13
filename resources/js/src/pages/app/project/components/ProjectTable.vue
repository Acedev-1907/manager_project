<script setup lang="ts">
import { ref } from 'vue';
import { GetProjectType, ProjectType } from '../actions/GetProject';
import SearchInput from '../../../../components/SearchInput.vue';
import { getUserData } from '../../../../helper/getUserData';
import { onMounted } from 'vue';

defineProps<{
    projects: GetProjectType;
    loading: boolean
}>();

const emit = defineEmits<{
    (e: 'pinnedProject', projectId: number): void
    (e: 'editProject', project: ProjectType): void
    (e: 'viewProjectDetail', projectId: number): void
    (e: 'getProject', page: number, query: string, showGlobalLoading: boolean): Promise<void>
    (e: 'deleteProject', projectId: number): void
}>()

const query = ref("");
const currentUserId = ref<number | null>(null);

// Lấy userId từ user-store (ưu tiên) hoặc userData (fallback)
import { useUserStore } from '../../../../state/userStore';
const userStore = useUserStore();

onMounted(() => {
    // Ưu tiên lấy từ user-store
    // @ts-expect-error - Pinia store type inference issue
    const userId = userStore.user?.id;
    currentUserId.value = userId ? Number(userId) : null;
    // Fallback: lấy từ userData (backward compatibility)
    if (!currentUserId.value) {
    const userData = getUserData();
        const fallbackId = (userData?.user as any)?.id;
        currentUserId.value = fallbackId ? Number(fallbackId) : null;
    }
});

const handleSearch = async (searchQuery: string) => {
    query.value = searchQuery;
    await emit("getProject", 1, searchQuery, false);
};
</script>

<template>
    <div class="project-table-container" style="position:relative;">
        <SearchInput v-model="query" placeholder="Search project..." :loading="loading" @search="handleSearch" />
        <div class="table-responsive table-card d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-header">
                    <tr>
                        <th width="7%">No.</th>
                        <th width="25%">Project Name</th>
                        <th width="15%">Creator</th>
                        <th width="15%">Members</th>
                        <th width="20%">Progress</th>
                        <th width="18%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!projects?.data?.data || projects?.data?.data.length === 0">
                        <td colspan="6" class="text-center text-muted">No data</td>
                    </tr>
                    <tr v-for="(project, idx) in projects?.data?.data" :key="project.id" class="table-row">
                        <td>{{ idx + 1 }}</td>
                        <td class="fw-bold">{{ project.name }}</td>
                        <td class="text-success">{{ project.creator?.name || 'N/A' }}</td>
                        <td>
                            <span v-if="project.users && project.users.length > 0">
                                <span v-for="user in project.users.slice(0, 3)" :key="user.id"
                                    class="badge bg-light text-dark me-1">
                                    {{ user.name }}
                                </span>
                                <span v-if="project.users.length > 3">+{{ project.users.length - 3 }}</span>
                            </span>
                            <span v-else>-</span>
                        </td>
                        <td>
                            <div class="progress custom-progress" role="progressbar"
                                :aria-valuenow="project?.task_progress?.progress" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-gradient-success"
                                    :style="{ width: (project?.task_progress?.progress || 0) + '%' }">
                                    {{ project?.task_progress?.progress || 0 }} %
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                                    @click="emit('editProject', project)" type="button"
                                    class="btn btn-outline-primary action-btn" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button @click="emit('pinnedProject', project.id)" type="button"
                                    class="btn btn-outline-warning action-btn" title="Pin">
                                    <i class="bi bi-pin-angle"></i>
                                </button>
                                <RouterLink class="btn btn-outline-success action-btn"
                                    :to="'/kaban?query=' + project.slug" title="View">
                                    <i class="bi bi-eye"></i>
                                </RouterLink>
                                <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                                    @click="emit('deleteProject', project.id)" type="button"
                                    class="btn btn-outline-danger action-btn" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Mobile Card List -->
        <div class="d-block d-md-none">
            <div v-if="!projects?.data?.data || projects?.data?.data.length === 0" class="text-center text-muted py-4">
                No data</div>
            <div v-for="project in projects?.data?.data" :key="project.id" class="mobile-project-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="mobile-id">#{{ project.id }}</span>
                    <span class="mobile-progress">
                        <span class="mobile-progress-label">{{ project?.task_progress?.progress || 0 }}%</span>
                    </span>
                </div>
                <div class="mobile-project-name mb-2">{{ project.name }}</div>
                <div class="progress custom-progress mb-2">
                    <div class="progress-bar bg-gradient-success"
                        :style="{ width: (project?.task_progress?.progress || 0) + '%' }"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                        @click="emit('editProject', project)" type="button"
                        class="btn btn-outline-primary action-btn-mobile">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button @click="emit('pinnedProject', project.id)" type="button"
                        class="btn btn-outline-warning action-btn-mobile">
                        <i class="bi bi-pin-angle"></i>
                    </button>
                    <RouterLink class="btn btn-outline-success action-btn-mobile" :to="'/kaban?query=' + project.slug">
                        <i class="bi bi-eye"></i>
                    </RouterLink>
                    <button v-if="currentUserId !== null && project.creator?.id === currentUserId"
                        @click="emit('deleteProject', project.id)" type="button"
                        class="btn btn-outline-danger action-btn-mobile">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center">
            <slot name="pagination"></slot>
        </div>
    </div>
</template>

<style scoped>
.project-table-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1rem 0.5rem;
}

.table-card {
    background: #fff;
    border-radius: 0.875rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    padding: 0.75rem;
    overflow-x: auto;
}

.table-header th {
    background: linear-gradient(90deg, #f8fafc 0%, #e3e9f7 100%);
    color: #333;
    font-weight: bold;
    border-top-left-radius: 0.625rem;
    border-top-right-radius: 0.625rem;
    border-bottom: 2px solid #e0e0e0;
    padding: 0.75rem 0.5rem;
    font-size: 0.9rem;
}

.table-row {
    transition: background 0.2s;
}

.table-row:hover {
    background: #f3f7fa;
}

.table-row td {
    padding: 0.75rem 0.5rem;
    font-size: 0.9rem;
}

.action-btn {
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: background 0.2s, color 0.2s;
}

.action-btn:hover {
    background: #f0f6ff;
    color: #007bff;
}

.custom-progress {
    border-radius: 0.875rem;
    background: #f1f3f6;
    height: 1.1rem;
    overflow: hidden;
}

.bg-gradient-success {
    background: linear-gradient(90deg, #4ade80 0%, #22d3ee 100%);
    color: #fff;
    font-weight: 500;
    border-radius: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: width 0.4s cubic-bezier(.4, 2.3, .3, 1);
}

@media (max-width: 768px) {
    .project-table-container {
        padding: 0.5rem 0.1rem;
    }

    .table-card {
        padding: 0.7rem;
    }

    .table-header th,
    .table td {
        font-size: 0.95rem;
        padding: 0.5rem 0.3rem;
    }

    .action-btn {
        width: 1.7rem;
        height: 1.7rem;
        font-size: 0.95rem;
    }

    .custom-progress {
        height: 0.9rem;
        font-size: 0.8rem;
    }

    /* Mobile card style */
    .mobile-project-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
        padding: 1.1rem 1rem 0.7rem 1rem;
        margin-bottom: 1.1rem;
        font-size: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .mobile-id {
        font-weight: bold;
        color: #4f46e5;
        font-size: 1.1rem;
    }

    .mobile-project-name {
        font-weight: 600;
        color: #22223b;
        font-size: 1.08rem;
        word-break: break-word;
    }

    .mobile-progress-label {
        font-size: 0.95rem;
        color: #16a34a;
        font-weight: 500;
    }

    .action-btn-mobile {
        border-radius: 50%;
        width: 2.1rem;
        height: 2.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin: 0 0.1rem;
        transition: background 0.2s, color 0.2s;
    }

    .action-btn-mobile:hover {
        background: #f0f6ff;
        color: #007bff;
    }
}
</style>