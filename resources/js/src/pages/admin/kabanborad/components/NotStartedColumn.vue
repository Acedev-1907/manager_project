<script lang="ts" setup>
import { getChar } from '../../../../helper/utils';
import { SingleProjectResponseType, TaskStatus } from '../actions/getProjectDetail.type';
import { taskStore } from '../store/kabanStore';
import { onMounted } from 'vue';

const isMobile = window.matchMedia('(max-width: 768px)').matches;

defineProps<{
    projectData: SingleProjectResponseType
}>()

const emit = defineEmits<{
    (e: 'openTaskModal'): Promise<void>
}>()

function handleDragStart(taskId: number, projectId: number) {
    taskStore.setDraggedTask(taskId, projectId);
}

// --- Touch drag for mobile with ghost effect ---
let touchTaskId: number | null = null;
let touchProjectId: number | null = null;
let touchMoveElem: HTMLElement | null = null;
let ghostElem: HTMLElement | null = null;

let autoScrollDirection: 'left' | 'right' | null = null;
let autoScrollFrame: number | null = null;

let lastTouchX: number | null = null;

function startAutoScroll(direction: 'left' | 'right') {
    if (autoScrollDirection === direction && autoScrollFrame) return;
    autoScrollDirection = direction;
    function step() {
        const row = document.querySelector('.card-body');
        if (row && autoScrollDirection) {
            const scrollAmount = 8;
            if (autoScrollDirection === 'right') {
                row.scrollLeft += scrollAmount;
            } else {
                row.scrollLeft -= scrollAmount;
            }
            autoScrollFrame = requestAnimationFrame(step);
        }
    }
    autoScrollFrame = requestAnimationFrame(step);
}

function stopAutoScroll() {
    autoScrollDirection = null;
    if (autoScrollFrame) {
        cancelAnimationFrame(autoScrollFrame);
        autoScrollFrame = null;
    }
}

function handleTouchStart(taskId: number, projectId: number, event: TouchEvent) {
    touchTaskId = taskId;
    touchProjectId = projectId;
    const target = event.target as HTMLElement;
    touchMoveElem = target.closest('.task_card') as HTMLElement;
    if (touchMoveElem) {
        // Tạo bóng task
        ghostElem = touchMoveElem.cloneNode(true) as HTMLElement;
        ghostElem.style.position = 'fixed';
        ghostElem.style.left = event.touches[0].clientX - 100 + 'px';
        ghostElem.style.top = event.touches[0].clientY - 30 + 'px';
        ghostElem.style.width = touchMoveElem.offsetWidth + 'px';
        ghostElem.style.pointerEvents = 'none';
        ghostElem.style.opacity = '0.8';
        ghostElem.style.zIndex = '9999';
        document.body.appendChild(ghostElem);
        // Ẩn task gốc
        touchMoveElem.style.opacity = '0';
    }
}

function handleTouchMove(event: TouchEvent) {
    if (ghostElem) {
        ghostElem.style.left = event.touches[0].clientX - 100 + 'px';
        ghostElem.style.top = event.touches[0].clientY - 30 + 'px';
    }
    const x = event.touches[0].clientX;
    const row = document.querySelector('.card-body');
    const edgeThreshold = 40;
    if (row) {
        if (x > window.innerWidth - edgeThreshold) {
            startAutoScroll('right');
        } else if (x < edgeThreshold) {
            startAutoScroll('left');
        } else {
            stopAutoScroll();
            if (lastTouchX !== null) {
                const delta = x - lastTouchX;
                if (Math.abs(delta) > 2) {
                    row.scrollLeft -= delta;
                }
            }
        }
    }
    lastTouchX = x;
}

function handleTouchEnd(event: TouchEvent) {
    stopAutoScroll();
    if (!touchTaskId || !touchProjectId) return;
    const touch = event.changedTouches[0];
    const elem = document.elementFromPoint(touch.clientX, touch.clientY);
    if (elem) {
        let dropCol: HTMLElement | null = elem.closest('.not_started_task, .pending_task, .completed_task') as HTMLElement;
        if (dropCol && !dropCol.classList.contains('not_started_task')) {
            taskStore.setDraggedTask(touchTaskId, touchProjectId);
            const dropEvent = new Event('drop', { bubbles: true });
            dropCol.dispatchEvent(dropEvent);
        }
    }
    if (touchMoveElem) {
        touchMoveElem.style.opacity = '';
    }
    if (ghostElem) {
        document.body.removeChild(ghostElem);
        ghostElem = null;
    }
    touchTaskId = null;
    touchProjectId = null;
    touchMoveElem = null;
    lastTouchX = null;
}

onMounted(() => {
    setTimeout(() => {
        document.querySelectorAll('.notStartedTask_').forEach((el) => {
            // nothing, just for reference
        });
    }, 500);
});
</script>
<template>
    <div class="not_started_task">
        <div class="card card-header">
            <button @click="emit('openTaskModal')" class="btn btn-warning">Add Task</button>
        </div>
        <div class="card-direct">
            <div v-for="task in projectData?.data?.tasks.filter(t => t.status === TaskStatus.NOT_STARTED)"
                :key="task.id" :draggable="!isMobile"
                @dragstart="!isMobile && handleDragStart(task.id, projectData?.data?.id)"
                @touchstart="isMobile && handleTouchStart(task.id, projectData?.data?.id, $event)"
                @touchmove="isMobile && handleTouchMove($event)" @touchend="isMobile && handleTouchEnd($event)"
                :class="'card card-body card-direct  task_card notStartedTask_' + task.id">
                <p>{{ task.name }}</p>
                <div class="assignees">
                    <template v-for="(member, index) in (task.task_members ? task.task_members.slice(0, 3) : [])"
                        :key="member.id">
                        <button :class="'btn btn-primary member_' + index">
                            {{ getChar(member?.members?.name) }}
                        </button>
                    </template>
                    <span v-if="task.task_members && task.task_members.length > 3">...</span>
                    {{ task.task_members ? task.task_members.length : 0 }} assignees
                </div>
            </div>
        </div>
    </div>
</template>