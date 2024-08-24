<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { loginInput } from '../../auth/action/login';
// import Error from '../../components/Error.vue';
// import BaseInput from '../../components/BaseInput.vue';
// import BaseBtn from '../../components/BaseBtn.vue';

const rules = {
    email: { required, email },
    password: { required }
}

// const v$ = useVuelidate(rules, loginInput);
// const { loading, login } = useLoginUser();

// async function submitLogin() {
//     const result = await v$.value.$validate();

//     if (!result) return;

//     await login();
//     v$.value.$reset()
// }

</script>

<template>
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <form>
                    <div class="form-group">
                        <div class="form-group">
                            <Error label="Name" :errors="v$.email.$errors" />
                            <BaseInput v-model="loginInput.email" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>