<script setup lang="ts">
import { ref, computed, watch } from 'vue';

interface ColumnConfig {
  key: string;
  title: string;
  icon: string;
  iconBg: string;
  status: number;
  color: string;
  colorLight: string;
  id?: number;
}

const props = defineProps<{
  visible: boolean;
  existingColumns: ColumnConfig[];
  editingColumn?: ColumnConfig | null;
}>();

const emit = defineEmits(['close', 'addColumn']);

const columnTitle = ref('');
const selectedIcon = ref('fas fa-circle');
const selectedColor = ref('#3b82f6');

const iconOptions = [
  { value: 'fas fa-circle', label: 'Circle' },
  { value: 'fas fa-clock', label: 'Clock' },
  { value: 'fas fa-check-circle', label: 'Check' },
  { value: 'fas fa-exclamation-circle', label: 'Exclamation' },
  { value: 'fas fa-star', label: 'Star' },
  { value: 'fas fa-heart', label: 'Heart' },
  { value: 'fas fa-flag', label: 'Flag' },
  { value: 'fas fa-bookmark', label: 'Bookmark' },
  { value: 'fas fa-tag', label: 'Tag' },
  { value: 'fas fa-bell', label: 'Bell' },
];

const colorOptions = [
  { value: '#3b82f6', label: 'Blue', light: '#60a5fa' },
  { value: '#f59e0b', label: 'Orange', light: '#fbbf24' },
  { value: '#10b981', label: 'Green', light: '#34d399' },
  { value: '#ef4444', label: 'Red', light: '#f87171' },
  { value: '#8b5cf6', label: 'Purple', light: '#a78bfa' },
  { value: '#06b6d4', label: 'Cyan', light: '#22d3ee' },
  { value: '#f97316', label: 'Orange', light: '#fb923c' },
  { value: '#84cc16', label: 'Lime', light: '#a3e635' },
];

const isValid = computed(() => {
  if (columnTitle.value.trim().length === 0) return false;

  // In edit mode, allow the same title if it's the same column
  if (props.editingColumn) {
    const otherColumns = props.existingColumns.filter(col => (col as any).id !== props.editingColumn?.id);
    return !otherColumns.some(col => col.title.toLowerCase() === columnTitle.value.toLowerCase());
  }

  // In create mode, check against all existing columns
  return !props.existingColumns.some(col => col.title.toLowerCase() === columnTitle.value.toLowerCase());
});

const isEditMode = computed(() => !!props.editingColumn);

const modalTitle = computed(() => {
  return isEditMode.value ? 'Edit Column' : 'Add New Column';
});

const buttonText = computed(() => {
  return isEditMode.value ? 'Update Column' : 'Add Column';
});

const nextStatus = computed(() => {
  const maxStatus = Math.max(...props.existingColumns.map(col => col.status), -1);
  return maxStatus + 1;
});

// Watch for editingColumn changes to populate form
watch(() => props.editingColumn, (newColumn) => {
  if (newColumn) {
    columnTitle.value = newColumn.title;
    selectedIcon.value = newColumn.icon;
    selectedColor.value = newColumn.color;
  } else {
    columnTitle.value = '';
    selectedIcon.value = 'fas fa-circle';
    selectedColor.value = '#3b82f6';
  }
}, { immediate: true });

function closeModal() {
  columnTitle.value = '';
  selectedIcon.value = 'fas fa-circle';
  selectedColor.value = '#3b82f6';
  emit('close');
}

function addColumn() {
  if (!isValid.value) return;

  const selectedColorOption = colorOptions.find(opt => opt.value === selectedColor.value);

  const columnData: ColumnConfig = {
    key: isEditMode.value ? props.editingColumn!.key : `column-${Date.now()}`,
    title: columnTitle.value.trim(),
    icon: selectedIcon.value,
    iconBg: `linear-gradient(135deg, ${selectedColor.value} 0%, ${selectedColorOption?.light || selectedColor.value} 100%)`,
    status: isEditMode.value ? props.editingColumn!.status : nextStatus.value,
    color: selectedColor.value,
    colorLight: selectedColorOption?.light || selectedColor.value,
  };

  // Add id for edit mode
  if (isEditMode.value && props.editingColumn) {
    columnData.id = props.editingColumn.id;
  }

  emit('addColumn', columnData);
  closeModal();
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' && isValid.value) {
    addColumn();
  } else if (event.key === 'Escape') {
    closeModal();
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">
            <i :class="isEditMode ? 'fas fa-edit' : 'fas fa-plus'"></i>
            {{ modalTitle }}
          </h3>
          <button class="close-btn" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Column Title</label>
            <input v-model="columnTitle" type="text" class="form-input" placeholder="Enter column title..."
              @keydown="handleKeydown" ref="titleInput" />
            <div v-if="columnTitle && !isValid" class="error-message">
              Column title is required and must be unique
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Icon</label>
            <div class="icon-grid">
              <button v-for="icon in iconOptions" :key="icon.value" class="icon-option"
                :class="{ active: selectedIcon === icon.value }" @click="selectedIcon = icon.value" type="button">
                <i :class="icon.value"></i>
                <span class="icon-label">{{ icon.label }}</span>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Color</label>
            <div class="color-grid">
              <button v-for="color in colorOptions" :key="color.value" class="color-option"
                :class="{ active: selectedColor === color.value }" :style="{ backgroundColor: color.value }"
                @click="selectedColor = color.value" type="button">
                <i v-if="selectedColor === color.value" class="fas fa-check"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModal">
            Cancel
          </button>
          <button class="btn btn-primary" :disabled="!isValid" @click="addColumn">
            {{ buttonText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 999 !important;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 24px 0 24px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 20px;
}

.modal-title {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-title i {
  color: #3b82f6;
}

.close-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #e2e8f0;
  color: #374151;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.error-message {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
}

.icon-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.icon-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 8px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 16px;
  color: #64748b;
}

.icon-option:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

.icon-option.active {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #3b82f6;
}

.icon-label {
  font-size: 10px;
  text-align: center;
  line-height: 1.2;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 8px;
}

.color-option {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border-color: #1e293b;
  transform: scale(1.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 0 24px 24px 24px;
}

.btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-secondary {
  background: #f1f5f9;
  color: #374151;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 16px;
  }

  .modal-content {
    max-width: 100%;
  }

  .modal-header {
    padding: 20px 20px 0 20px;
  }

  .modal-title {
    font-size: 18px;
  }

  .modal-body {
    padding: 20px;
  }

  .icon-grid {
    grid-template-columns: repeat(4, 1fr);
  }

  .color-grid {
    grid-template-columns: repeat(6, 1fr);
  }

  .modal-footer {
    padding: 0 20px 20px 20px;
  }
}

@media (max-width: 480px) {
  .modal-overlay {
    padding: 12px;
  }

  .modal-header {
    padding: 16px 16px 0 16px;
  }

  .modal-title {
    font-size: 16px;
  }

  .modal-body {
    padding: 16px;
  }

  .icon-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .color-grid {
    grid-template-columns: repeat(4, 1fr);
  }

  .modal-footer {
    padding: 0 16px 16px 16px;
  }

  .btn {
    padding: 8px 16px;
    font-size: 13px;
  }
}
</style>