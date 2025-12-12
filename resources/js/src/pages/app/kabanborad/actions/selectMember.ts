import { ref } from "vue";
import { showError } from "../../../../helper/alert";
import { taskStore } from "../store/kabanStore";

export function useSelectMember() {
  const selectedMembers = ref<
    Array<{ id: number; name: string; email: string }>
  >([]);
  function selectMember(member: { id: number; name: string; email: string }) {
    // Ensure memberIds is always an array
    if (!Array.isArray(taskStore.taskInput.memberIds)) {
      taskStore.taskInput.memberIds = [];
    }
    const exist = selectedMembers.value.filter(
      (memberItem) => memberItem.id === member.id
    );
    if (exist.length === 0) {
      selectedMembers.value.push({
        id: member.id,
        name: member.name,
        email: member.email,
      });
      taskStore.taskInput.memberIds.push(member.id);
    } else {
      showError("You have already selected this member!");
    }
  }

  function unSelectedMember(memberId: number) {
    // Ensure memberIds is always an array
    if (!Array.isArray(taskStore.taskInput.memberIds)) {
      taskStore.taskInput.memberIds = [];
    }
    const filteredMembers = selectedMembers.value.filter(
      (memberItem) => memberItem.id !== memberId
    );
    const filteredMemberIds = taskStore.taskInput.memberIds.filter(
      (item) => item !== memberId
    );

    taskStore.taskInput.memberIds = filteredMemberIds;
    selectedMembers.value = filteredMembers;
  }
  return { selectMember, selectedMembers, unSelectedMember };
}
