<script lang="ts" setup>
import { onMounted } from 'vue';
import MemberTable from './components/MemberTable.vue';
import { useGetMembers } from './actions/getMember';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';


const { getMembers, memberData, loading } = useGetMembers()

async function showListOfMembers() {
    await getMembers()
}
console.log('Pagination Data:', memberData.value);

onMounted(async () => {
    showListOfMembers()
})

</script>
<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Member
                        <RouterLink style="float:right" to="/create-members" class="btn btn-primary">Create Member
                        </RouterLink>
                    </div>
                    <div class="card-body">
                        <MemberTable :loading="loading" @getMember="getMembers" :members="memberData">
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