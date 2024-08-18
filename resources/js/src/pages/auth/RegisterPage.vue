<script lang="ts" setup>
import useVuelidate from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { registerInput, useRegisterUser } from './action/register';
import Error from '../../components/Error.vue';

const rules = {
    email: { required, email },
    password: { required }
}

const v$ = useVuelidate(rules, registerInput);
const { loading, register } = useRegisterUser();
async function submitRegister() {
    const result = await v$.value.$validate();

    if (!result) return;
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
                        <br />
                        <form @submit.prevent="submitRegister">
                            <div class="form-group">
                                <Error label="Email" :errors="v$.email.$errors">
                                    <input v-model="registerInput.email" type="text" name="" class="form-control"
                                        placeholder="">
                                </Error>
                            </div>

                            <div class="form-group">
                                <Error label="Password" :errors="v$.password.$errors">
                                    <input v-model="registerInput.password" type="text" name="" class="form-control"
                                        placeholder="">
                                </Error>
                            </div>
                            <div class="form-group mt-3">
                                <button class="btn btn-primary form-control">Register</button>
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