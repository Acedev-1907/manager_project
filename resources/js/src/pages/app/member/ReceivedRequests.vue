<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { getReceivedMemberRequests, respondMemberRequest, MemberRequest } from './actions/getMember';
import LoadingPage from '../../../components/LoadingPage.vue';
import { useRouter } from 'vue-router';

const receivedRequests = ref<MemberRequest[]>([]);
const loading = ref(true);
const router = useRouter();

async function fetchReceivedRequests() {
  loading.value = true;
  try {
    const res = await getReceivedMemberRequests();
    receivedRequests.value = res.data || [];
  } catch {
    receivedRequests.value = [];
  }
  loading.value = false;
}

async function handleRespond(requestId: number, status: 'accepted' | 'rejected') {
  try {
    loading.value = true;
    console.log(`Handling ${status} for request ID: ${requestId}`);

    const response = await respondMemberRequest(requestId, status);
    console.log('Response received:', response);

    if (status === 'accepted') {
      // Thêm thông báo thành công khi chấp nhận
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Success!',
          text: 'You have accepted this request',
          icon: 'success',
          timer: 1500
        });
      }
    }

    await fetchReceivedRequests();
  } catch (error) {
    console.error('Error responding to request:', error);

    // Hiển thị thông báo lỗi cho người dùng
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Error!',
        text: 'There was a problem processing your request. Please try again.',
        icon: 'error',
      });
    }

    // Thử tải lại danh sách sau lỗi
    await fetchReceivedRequests();
  } finally {
    loading.value = false;
  }
}

onMounted(fetchReceivedRequests);
</script>
<template>
  <div class="container py-4">
    <h4><i class="bi bi-inbox"></i> Received Member Requests</h4>
    <LoadingPage v-if="loading" />
    <div v-else>
      <div v-if="!receivedRequests.length" class="alert alert-info mt-3">
        Không có lời mời nào nhận được.
      </div>
      <div v-else class="mt-3">
        <div v-for="req in receivedRequests" :key="req.id" class="card p-2 mb-2 d-flex flex-row align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <img :src="req.sender?.avatar || 'https://ui-avatars.com/api/?name=' + (req.sender?.name || '')" style="width:32px;height:32px;border-radius:50%" />
            <span class="fw-bold">{{ req.sender?.name || 'Unknown' }}</span>
            <span class="text-muted small">({{ req.sender?.email || '' }})</span>
            <span class="badge bg-secondary ms-2">{{ req.status }}</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">{{ new Date(req.created_at).toLocaleString() }}</span>
            <template v-if="req.status === 'pending'">
              <button class="btn btn-success btn-sm" @click="handleRespond(req.id, 'accepted')">Accept</button>
              <button class="btn btn-outline-danger btn-sm" @click="handleRespond(req.id, 'rejected')">Reject</button>
            </template>
            <template v-else>
              <span class="text-muted small">Responded</span>
            </template>
          </div>
        </div>
      </div>
    </div>
    <router-link class="btn btn-link mt-3" to="/app/members">
      <i class="bi bi-arrow-left"></i> Quay lại Members
    </router-link>
  </div>
</template> 