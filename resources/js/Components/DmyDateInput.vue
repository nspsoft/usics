<script setup>
import { ref, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
});

const emit = defineEmits(['update:modelValue']);

const isoToDmy = (iso) => {
    if (!iso) return '';
    const parts = String(iso).split('T')[0].split('-');
    if (parts.length !== 3) return '';
    const [y, m, d] = parts;
    if (!y || !m || !d) return '';
    return `${d.padStart(2, '0')}/${m.padStart(2, '0')}/${y}`;
};

const dmyToIso = (dmy) => {
    const v = String(dmy || '').trim();
    if (v === '') return '';
    const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return null;
    const dd = parseInt(m[1], 10);
    const mm = parseInt(m[2], 10);
    const yyyy = parseInt(m[3], 10);
    if (!yyyy || mm < 1 || mm > 12 || dd < 1 || dd > 31) return null;
    const iso = `${String(yyyy).padStart(4, '0')}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
    const dt = new Date(`${iso}T00:00:00`);
    if (Number.isNaN(dt.getTime())) return null;
    if (dt.getUTCFullYear() !== yyyy || dt.getUTCMonth() + 1 !== mm || dt.getUTCDate() !== dd) return null;
    return iso;
};

const display = ref(isoToDmy(props.modelValue));

watch(() => props.modelValue, (val) => {
    const next = isoToDmy(val);
    if (next !== display.value) display.value = next;
});

const onInput = (e) => {
    display.value = e.target.value;
    const iso = dmyToIso(display.value);
    if (iso === '') emit('update:modelValue', '');
    if (iso) emit('update:modelValue', iso);
};

const onBlur = () => {
    const iso = dmyToIso(display.value);
    if (iso === null) {
        display.value = isoToDmy(props.modelValue);
    }
};
</script>

<template>
    <input
        v-bind="$attrs"
        :value="display"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        placeholder="DD/MM/YYYY"
        @input="onInput"
        @blur="onBlur"
    />
</template>

