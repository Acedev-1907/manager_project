<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { loginInput, useLoginUser } from './action/login';

const rules = {
    email: { required, email },
    password: { required }
}

const v$ = useVuelidate(rules, loginInput);
const { loading, login } = useLoginUser();

async function submitLogin() {
    const result = await v$.value.$validate();

    if (!result) return;

    await login();
    v$.value.$reset()
}

</script>

<template>
    <div class="row">
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 align="center">Login</h2>
                        <br />
                        <form @submit.prevent="submitLogin">
                            <div class="form-group">
                                <Error label="Email" :errors="v$.email.$errors" />
                                <BaseInput v-model="loginInput.email" />
                            </div>

                            <div class="form-group">
                                <Error label="Password" :errors="v$.password.$errors" />
                                <BaseInput v-model="loginInput.password" type="password" />
                            </div>
                            <div class="form-group">
                                <RouterLink to="/register">Create an account</RouterLink>
                            </div>
                            <div class="form-group mt-3">
                                <BaseBtn label="login" :loading="loading"></BaseBtn>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">

            </div>
        </div>
    </div>
</template>