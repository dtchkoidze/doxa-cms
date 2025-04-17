<template>

    <!-- Filter Panel -->
    <Drawer width="350px" ref="filterDrawer" v-if="$parent.available.columns.length">

        <template #toggle>
            <button
                class="relative btn px-2.5 bg-white dark:bg-gray-800 border-gray-200 hover:border-gray-300 dark:border-gray-700/60 dark:hover:border-gray-600 text-gray-400 dark:text-gray-500"
                aria-haspopup="true">
                <span class="sr-only">Filter</span><wbr />
                <svg class="fill-current" :class="$parent.applied.filters.columns.length > 1 ? 'text-green-500' : ''" width="16" height="16" viewBox="0 0 16 16">
                    <path
                        d="M0 3a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1ZM3 8a1 1 0 0 1 1-1h8a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1ZM7 12a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2H7Z" />
                </svg>
            </button>
        </template>

        <!-- Drawer Header -->
        <template #header>
            <div class="flex items-center justify-between p-3">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ vocab['filters.title'] }}
                </p>
            </div>
        </template>

        <!-- Drawer Content -->
        <template #content>
            <div class="flex-1 p-5 overflow-auto max-sm:px-4">
                <div v-for="column in $parent.available.columns">
                    <div v-if="column.filterable">

                        <!-- Boolean -->
                        <div v-if="column.type === 'boolean' || column.type === 'bool'">

                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium leading-6 text-gray-800 dark:text-white"
                                    v-text="column.label">
                                </p>

                                <div class="flex items-center gap-x-1.5"
                                    @click="removeAppliedColumnAllValues(column.index)">
                                    <p class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                        v-if="hasAnyAppliedColumnValues(column.index)">
                                        {{ vocab['filters.custom-filters.clear-all'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-2 mt-1.5">
                                <dropdown>

                                    <template #toggle>
                                        <button type="button"
                                            class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400">
                                            <span class="text-sm text-gray-400 dark:text-gray-400"
                                                v-text="vocab['filters.select']">
                                            </span>

                                            <span class="text-2xl icon-sort-down"></span>
                                        </button>
                                    </template>

                                    <template #menu>
                                        <template v-for="option in column.options">
                                            <li class="px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                v-text="option.label"
                                                @click="$parent.filterPage(option.value, column)" />
                                        </template>
                                    </template>

                                </dropdown>

                            </div>

                            <div class="flex flex-wrap gap-2 mb-4">
                                <p class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)">
                                    <!-- Retrieving the label from the options based on the applied column value. -->
                                    <span
                                        v-text="column.options.find((option => option.value == appliedColumnValue)).label"></span>

                                    <span class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                        @click="removeAppliedColumnValue(column.index, appliedColumnValue)">
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- related -->
                        <div v-else-if="column.type === 'related'">
                            <!-- Basic -->
                            <div v-if="column.options.type === 'basic'">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium leading-6 text-gray-800 dark:text-white"
                                        v-text="column.label">
                                    </p>

                                    <div class="flex items-center gap-x-1.5"
                                        @click="removeAppliedColumnAllValues(column.index)">
                                        <p class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                            v-if="hasAnyAppliedColumnValues(column.index)">
                                            {{ vocab['filters.custom-filters.clear-all'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mb-2 mt-1.5">
                                    <dropdown>
                                        <template #toggle>
                                            <button type="button"
                                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400">
                                                <span class="text-sm text-gray-400 dark:text-gray-400"
                                                    v-text="vocab['filters.select']">
                                                </span>

                                                <span class="text-2xl icon-sort-down"></span>
                                            </button>
                                        </template>

                                        <template #menu>
                                            <template v-for="option in column.options.params.options">
                                                <li class="px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                    v-text="option.title"
                                                    @click="$parent.filterPage(option.id, column)" />
                                            </template>
                                        </template>

                                    </dropdown>

                                </div>

                                <div class="flex flex-wrap gap-2 mb-4">
                                    <p class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)">
                                        <!-- Retrieving the label from the options based on the applied column value. -->
                                        <span
                                            v-text="column.options.params.options.find((option => option.id == appliedColumnValue)).title"></span>

                                        <span class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)">
                                        </span>
                                    </p>
                                </div>
                            </div>

                        </div>

                        <!-- Dropdown -->
                        <div v-else-if="column.type === 'dropdown'">
                            <!-- Basic -->
                            <div v-if="column.options.type === 'basic'">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium leading-6 text-gray-800 dark:text-white"
                                        v-text="column.label">
                                    </p>

                                    <div class="flex items-center gap-x-1.5"
                                        @click="removeAppliedColumnAllValues(column.index)">
                                        <p class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                            v-if="hasAnyAppliedColumnValues(column.index)">
                                            {{ vocab['filters.custom-filters.clear-all'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mb-2 mt-1.5">
                                    <dropdown>
                                        <template #toggle>
                                            <button type="button"
                                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400">
                                                <span class="text-sm text-gray-400 dark:text-gray-400"
                                                    v-text="vocab['filters.select']">
                                                </span>

                                                <span class="text-2xl icon-sort-down"></span>
                                            </button>
                                        </template>

                                        <template #menu>
                                            <template v-for="option in column.options.params.options">
                                                <li class="px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                    v-text="option.label"
                                                    @click="$parent.filterPage(option.value, column)" />
                                            </template>
                                        </template>

                                    </dropdown>

                                </div>

                                <div class="flex flex-wrap gap-2 mb-4">
                                    <p class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)">
                                        <!-- Retrieving the label from the options based on the applied column value. -->
                                        <span
                                            v-text="column.options.params.options.find((option => option.value == appliedColumnValue)).label"></span>

                                        <span class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)">
                                        </span>
                                    </p>
                                </div>
                            </div>

                        </div>

                        <!-- Date Range -->
                        <div v-else-if="column.type === 'date_range'">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium leading-6 dark:text-white" v-text="column.label">
                                </p>

                                <div class="flex items-center gap-x-1.5"
                                    @click="removeAppliedColumnAllValues(column.index)">
                                    <p class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                        v-if="hasAnyAppliedColumnValues(column.index)">
                                        {{ vocab['filters.custom-filters.clear-all'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                                <p class="px-3 py-2 text-sm font-medium leading-6 text-center text-gray-600 transition-all border rounded-md cursor-pointer hover:border-gray-400 dark:border-gray-800 dark:text-gray-300 dark:hover:border-gray-400"
                                    v-for="option in column.options" v-text="option.label" @click="$parent.filterPage(
                                        $event,
                                        column,
                                        { quickFilter: { isActive: true, selectedFilter: option } }
                                    )">
                                </p>

                                <!-- <datepicker ::allow-input="false">
                                    <input value=""
                                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                        :type="column.input_type" :name="`${column.index}[from]`" placeholder="From"
                                        :ref="`${column.index}[from]`" @change="$parent.filterPage(
                                            $event,
                                            column,
                                            { range: { name: 'from' }, quickFilter: { isActive: false } }
                                        )" />
                                </datepicker>

                                <datepicker ::allow-input="false">
                                    <input value=""
                                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                        :type="column.input_type" :name="`${column.index}[to]`" placeholder="To"
                                        :ref="`${column.index}[to]`" @change="$parent.filterPage(
                                            $event,
                                            column,
                                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                                        )" />
                                </datepicker> -->

                                <div class="flex flex-wrap gap-2 mb-4">
                                    <p class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)">
                                        <span v-text="appliedColumnValue.join(' to ')"></span>

                                        <span class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)">
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Any -->
                        <div v-else>
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium leading-6 dark:text-white" v-text="column.label">
                                </p>

                                <div class="flex items-center gap-x-1.5"
                                    @click="removeAppliedColumnAllValues(column.index)">
                                    <p class="text-sm font-medium leading-6 text-blue-400 cursor-pointer"
                                        v-if="hasAnyAppliedColumnValues(column.index)">
                                        {{ vocab['filters.custom-filters.clear-all'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-2 mt-1.5 grid">
                                <input type="text" class="w-full form-input" :name="column.index"
                                    :placeholder="column.label" @keyup.enter="$parent.filterPage($event, column)" />
                            </div>

                            <div class="flex flex-wrap gap-2 mb-4">
                                <p class="flex items-center px-2 py-0.5 text-sm text-white bg-gray-600 rounded"
                                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)">
                                    <span v-text="appliedColumnValue"></span>

                                    <span class="cursor-pointer  text-white ltr:ml-1.5 rtl:mr-1.5"
                                        @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                    </span>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </template>

    </Drawer>

</template>

<script>

import Drawer from "../components/drawer.vue";
// import Chlo from "../tools/chlo.vue";
import dropdown from "../components/dropdown.vue";
// import datepicker from "../datepicker/date.vue";

export default {
    inject: ['vocab'],
    components: {
        Drawer,
        //Chlo,
        dropdown,
        // datepicker,
    },
    methods: {
        removeAppliedColumnAllValues(columnIndex) {
            this.$parent.applied.filters.columns = this.$parent.applied.filters.columns.filter(column => column.index !==
                columnIndex);
            this.$parent.get();
        },
        removeAppliedColumnValue(columnIndex, appliedColumnValue) {
            let appliedColumn = this.$parent.findAppliedColumn(columnIndex);
            appliedColumn.value = appliedColumn?.value.filter(value => value !== appliedColumnValue);

            /**
             * Clean up is done here. If there are no applied values present, there is no point in including the applied column as well.
             */
            if (!appliedColumn.value.length) {
                this.$parent.applied.filters.columns = this.$parent.applied.filters.columns.filter(column => column
                    .index !== columnIndex);
            }
            this.$parent.get();
        },
        hasAnyAppliedColumnValues(columnIndex) {
            let appliedColumn = this.$parent.findAppliedColumn(columnIndex);
            return appliedColumn?.value.length > 0;
        },
        getAppliedColumnValues(columnIndex) {
            let appliedColumn = this.$parent.findAppliedColumn(columnIndex);
            return appliedColumn?.value ?? [];
        },
    },
};

</script>
