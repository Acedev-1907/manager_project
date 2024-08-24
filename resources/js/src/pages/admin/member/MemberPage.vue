<script lang="ts" setup>
import MemberTable from './components/MemberTable.vue';

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
                        <MemberTable />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>