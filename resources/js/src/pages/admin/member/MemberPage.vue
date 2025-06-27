<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import MemberTable from './components/MemberTable.vue';
import { MemberType, useGetMembers } from './actions/getMember';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { memberStore } from './store/MemberStore';
import router from '../../../router';
import { MemberInputType } from './actions/createMember';
import LoadingPage from '../../../components/LoadingPage.vue';


const { getMembers, memberData, loading } = useGetMembers()
const isLoading = ref(true);

async function showListOfMembers() {
    await getMembers()
}

function editMember(member: MemberType) {
    memberStore.memberInput = member
    memberStore.edit = true
    router.push('/create-members')
}
onMounted(async () => {
    isLoading.value = true;
    await showListOfMembers()
    memberStore.edit = false
    memberStore.memberInput = {} as MemberInputType
    isLoading.value = false;
})

</script>
<template>
    <div class="container">
        <LoadingPage v-if="isLoading" />
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Member
                        <RouterLink style="float:right" to="/create-members" class="btn btn-primary">Create Member
                        </RouterLink>
                    </div>
                    <div class="card-body">
                        <MemberTable @editMember="editMember" :loading="loading" @getMember="getMembers"
                            :members="memberData">
                            <template #pagination>
                                <Bootstrap5Pagination v-if="memberData?.data" :data="memberData.data"
                                    @pagination-change-page="getMembers" />
                            </template>
                        </MemberTable>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>