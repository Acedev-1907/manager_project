<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { registerInput, useRegisterUser } from './action/register';

const rules = {
    email: { required, email },
    password: { required }
}

const v$ = useVuelidate(rules, registerInput);
const { loading, register } = useRegisterUser();
async function submitRegister() {
    const result = await v$.value.$validate();

    if (!result) return;

    await register();
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
                        <h2 align="center">
                            Register
                        </h2>
                        <br />
                        {{ registerInput }}
                        <form @submit.prevent="submitRegister">
                            <div class="form-group">
                                <Error label="Email" :errors="v$.email.$errors">
                                    <BaseInput v-model="registerInput.email" />
                                </Error>
                            </div>

                            <div class="form-group">
                                <Error label="Password" :errors="v$.password.$errors">
                                    <BaseInput v-model="registerInput.password" type="password" />
                                </Error>
                            </div>
                            </br>
                            <div class="form-group">
                                <RouterLink to="/login">Login into your account</RouterLink>
                            </div>
                            <div class="form-group mt-3">
                                <BaseBtn label="register" :loading="loading"></BaseBtn>
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