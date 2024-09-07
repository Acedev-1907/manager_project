import { defineStore } from 'pinia';
import { MemberInputType } from '../actions/createMember';

const userMemberStore = defineStore('member', {
    state: () => ({
        memberInput: {} as MemberInputType,
        edit: false
    })
})

export const memberStore = userMemberStore();