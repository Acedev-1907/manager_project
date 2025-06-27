<script lang="ts" setup>
import { ref } from 'vue';
import { GetMemberType, MemberType } from '../actions/getMember';
import { myDebounce } from '../../../../helper/utils';


defineProps<{
    members: GetMemberType;
    loading: boolean;
}>()

const emit = defineEmits<{
    (e: "editMember", member: MemberType): void;
    (e: "getMember", page: number, query: string, showGlobalLoading: boolean): Promise<void>;
}>();

const query = ref("");
const search = myDebounce(async function () {
    await emit("getMember", 1, query.value, false);
}, 200);

</script>
<template>
    <div class="member-table-container" style="position:relative;">
        <div class="search-bar-card">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <i class="bi bi-search"></i>
                </span>
                <input @keydown="search" v-model="query" placeholder="Search member..." class="search-input-beauty" />
                <span v-if="query" class="clear-icon" @click="query = ''">
                    <i class="bi bi-x-circle"></i>
                </span>
                <span v-show="loading" class="loading-spinner">
                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                </span>
            </div>
        </div>
        <div class="table-responsive table-card d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-header">
                    <tr>
                        <th width="10%">ID</th>
                        <th width="40%">Name</th>
                        <th width="40%">Email</th>
                        <th width="10%">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!members?.data?.data || members?.data?.data.length === 0">
                        <td colspan="4" class="text-center text-muted">No data</td>
                    </tr>
                    <tr v-for="member in members?.data?.data" :key="member.id" class="table-row">
                        <td>{{ member.id }}</td>
                        <td class="fw-bold">{{ member.name }}</td>
                        <td>{{ member.email }}</td>
                        <td>
                            <button @click="emit('editMember', member)" type="button"
                                class="btn btn-outline-primary action-btn">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Mobile Card List -->
        <div class="d-block d-md-none">
            <div v-if="!members?.data?.data || members?.data?.data.length === 0" class="text-center text-muted py-4">
                No data</div>
            <div v-for="member in members?.data?.data" :key="member.id" class="mobile-member-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="mobile-id">#{{ member.id }}</span>
                </div>
                <div class="mobile-member-name mb-1">{{ member.name }}</div>
                <div class="mobile-member-email mb-2">{{ member.email }}</div>
                <div class="d-flex justify-content-end mt-2">
                    <button @click="emit('editMember', member)" type="button"
                        class="btn btn-outline-primary action-btn-mobile">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="p-3 d-flex justify-content-center">
            <slot name="pagination"></slot>
        </div>
    </div>
</template>
<style scoped>
.member-table-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem 0.5rem;
}

.search-bar-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    padding: 1rem 1.5rem 1rem 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.search-input-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
    background: #f8fafc;
    border-radius: 2.5rem;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
    padding: 0.05rem 0.7rem 0.05rem 0.5rem;
    transition: box-shadow 0.2s, border 0.2s;
}

.search-input-beauty {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.98rem;
    padding: 0.45rem 0.5rem 0.45rem 1.7rem;
    border-radius: 2.5rem;
    color: #22223b;
    transition: box-shadow 0.2s, border 0.2s;
}

.search-input-beauty:focus {
    box-shadow: 0 0 0 2px #a5b4fc;
    background: #fff;
}

.search-icon {
    position: absolute;
    left: 0.5rem;
    color: #94a3b8;
    font-size: 1.05rem;
    z-index: 2;
    pointer-events: none;
}

.clear-icon {
    position: absolute;
    right: 1.7rem;
    color: #cbd5e1;
    font-size: 1.05rem;
    cursor: pointer;
    z-index: 2;
    transition: color 0.2s;
}

.clear-icon:hover {
    color: #ef4444;
}

.loading-spinner {
    position: absolute;
    right: 0.5rem;
    display: flex;
    align-items: center;
    top: 50%;
    transform: translateY(-50%);
}

.table-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    padding: 1rem;
    overflow-x: auto;
}

.table-header th {
    background: linear-gradient(90deg, #f8fafc 0%, #e3e9f7 100%);
    color: #333;
    font-weight: bold;
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
    border-bottom: 2px solid #e0e0e0;
}

.table-row {
    transition: background 0.2s;
}

.table-row:hover {
    background: #f3f7fa;
}

.action-btn {
    border-radius: 50%;
    width: 2.2rem;
    height: 2.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: background 0.2s, color 0.2s;
}

.action-btn:hover {
    background: #f0f6ff;
    color: #007bff;
}

@media (max-width: 768px) {
    .member-table-container {
        padding: 0.5rem 0.1rem;
    }

    .search-bar-card,
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

    /* Mobile card style */
    .mobile-member-card {
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

    .mobile-member-name {
        font-weight: 600;
        color: #22223b;
        font-size: 1.08rem;
        word-break: break-word;
    }

    .mobile-member-email {
        color: #64748b;
        font-size: 0.97rem;
        margin-bottom: 0.2rem;
        word-break: break-all;
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

    .search-bar-card {
        padding: 0.7rem;
    }

    .search-input-beauty {
        font-size: 0.93rem;
        padding: 0.38rem 0.4rem 0.38rem 1.3rem;
    }

    .search-input-wrapper {
        padding: 0.01rem 0.3rem 0.01rem 0.2rem;
    }

    .search-icon {
        left: 0.3rem;
        font-size: 0.95rem;
    }

    .clear-icon {
        right: 1.1rem;
        font-size: 0.95rem;
    }
}
</style>