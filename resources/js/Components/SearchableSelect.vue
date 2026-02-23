<script setup>
import { ref, computed } from 'vue';
import {
    Combobox,
    ComboboxInput,
    ComboboxButton,
    ComboboxOptions,
    ComboboxOption,
    TransitionRoot,
} from '@headlessui/vue';
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: [String, Number, Object],
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Select option...',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const query = ref('');

const filteredOptions = computed(() => {
    if (!query.value) return props.options;
    
    return props.options.filter((option) => {
        const label = option?.label ? String(option.label) : '';
        return label
            .toLowerCase()
            .replace(/\s+/g, '')
            .includes(query.value.toLowerCase().replace(/\s+/g, ''));
    });
});

const selectedOption = computed({
    get: () => props.options.find(opt => opt.id == props.modelValue) || null,
    set: (val) => {
        emit('update:modelValue', val?.id);
        emit('change', val);
    }
});
</script>

<template>
    <div class="w-full">
        <Combobox v-model="selectedOption" nullable>
            <div class="relative z-[9999]">
                <div class="relative w-full cursor-default overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:text-sm border border-slate-200 dark:border-slate-700">
                    <ComboboxInput
                        class="w-full border-none py-2.5 pl-3 pr-10 text-xs leading-5 text-slate-900 dark:text-white bg-transparent focus:ring-0"
                        :displayValue="(option) => option?.label ?? ''"
                        @change="query = $event.target.value"
                        :placeholder="placeholder"
                        :title="selectedOption?.label"
                    />
                    <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <ChevronUpDownIcon class="h-5 w-5 text-slate-400" aria-hidden="true" />
                    </ComboboxButton>
                </div>
                <TransitionRoot
                    leave="transition ease-in duration-100"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                    @after-leave="query = ''"
                >
                    <ComboboxOptions class="absolute mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white dark:bg-slate-800 py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm z-[9999] border border-slate-200 dark:border-slate-700">
                        <div
                            v-if="filteredOptions.length === 0 && query !== ''"
                            class="relative cursor-default select-none py-2 px-4 text-slate-400"
                        >
                            Nothing found.
                        </div>

                        <ComboboxOption
                            v-for="option in filteredOptions"
                            as="template"
                            :key="option.id"
                            :value="option"
                            v-slot="{ selected, active }"
                        >
                            <li
                                class="relative cursor-default select-none py-2 pl-10 pr-4 text-xs break-words"
                                :class="{
                                    'bg-blue-600 text-white': active,
                                    'text-slate-600 dark:text-slate-300': !active,
                                }"
                            >
                                <span class="block whitespace-normal leading-tight" :class="{ 'font-medium': selected, 'font-normal': !selected }">
                                    {{ option.label }}
                                </span>
                                <span
                                    v-if="selected"
                                    class="absolute inset-y-0 left-0 flex items-center pl-3"
                                    :class="{ 'text-white': active, 'text-blue-500': !active }"
                                >
                                    <CheckIcon class="h-4 w-4" aria-hidden="true" />
                                </span>
                            </li>
                        </ComboboxOption>
                    </ComboboxOptions>
                </TransitionRoot>
            </div>
        </Combobox>
    </div>
</template>
