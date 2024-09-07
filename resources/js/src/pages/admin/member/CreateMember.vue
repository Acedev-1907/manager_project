<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { useCreateOrUpdateMember } from './actions/createMember';
import Error from '../../../components/Error.vue';
import BaseInput from '../../../components/BaseInput.vue';
import BaseBtn from '../../../components/BaseBtn.vue';
import { memberStore } from './store/MemberStore';
const rules = {
    email: { required, email },
    name: { required }
}

const v$ = useVuelidate(rules, memberStore.memberInput);
const { loading, createOrUpdate } = useCreateOrUpdateMember();

async function submitMember() {
    const result = await v$.value.$validate();

    if (!result) return;

    await createOrUpdate();
    v$.value.$reset()
}

</script>

<template>
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h3>Create Member</h3>
                <br />
                <form @submit.prevent="submitMember">
                    <div class="form-group">
                        <div class="form-group">
                            <Error label="Name" :errors="v$.name.$errors" />
                            <BaseInput v-model="memberStore.memberInput.name" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-group">
                            <Error label="Email" :errors="v$.email.$errors" />
                            <BaseInput v-model="memberStore.memberInput.email" />
                        </div>
                    </div>
                    <br />
                    <RouterLink to="/members">See members list</RouterLink>
                    <div class="form-group mt-3">
                        <BaseBtn :class="memberStore.edit ? 'btn btn-warning' : 'btn btn-primary'"
                            :label="memberStore.edit ? 'Update Membre' : 'Create Member'" :loading="loading" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>