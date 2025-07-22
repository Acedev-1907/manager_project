<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { getSentMemberRequests, MemberRequest } from './actions/getMember';
import LoadingPage from '../../../components/LoadingPage.vue';
import { useRouter } from 'vue-router';

const sentRequests = ref<MemberRequest[]>([]);
const loading = ref(true);
const router = useRouter();

async function fetchSentRequests() {
  loading.value = true;
  try {
    const res = await getSentMemberRequests();
    sentRequests.value = res.data || [];
  } catch {
    sentRequests.value = [];
  }
  loading.value = false;
}

onMounted(fetchSentRequests);
</script>
<template>
  <div class="container py-4">
    <h4><i class="bi bi-send"></i> Sent Member Requests</h4>
    <LoadingPage v-if="loading" />
    <div v-else>
      <div v-if="!sentRequests.length" class="alert alert-info mt-3">
        Không có lời mời nào đã gửi.
      </div>
      <div v-else class="mt-3">
        <div v-for="req in sentRequests" :key="req.id" class="card p-2 mb-2 d-flex flex-row align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <img :src="req.receiver?.avatar || 'https://ui-avatars.com/api/?name=' + (req.receiver?.name || '')" style="width:32px;height:32px;border-radius:50%" />
            <span class="fw-bold">{{ req.receiver?.name || 'Unknown' }}</span>
            <span class="text-muted small">({{ req.receiver?.email || '' }})</span>
            <span class="badge bg-secondary ms-2">{{ req.status }}</span>
          </div>
          <span class="text-muted small">{{ new Date(req.created_at).toLocaleString() }}</span>
        </div>
      </div>
    </div>
    <router-link class="btn btn-link mt-3" to="/app/members">
      <i class="bi bi-arrow-left"></i> Quay lại Members
    </router-link>
  </div>
</template> 