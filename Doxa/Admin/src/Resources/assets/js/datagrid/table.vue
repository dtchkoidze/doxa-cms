<template>
    <div class="relative bg-white shadow-sm dark:bg-gray-800 rounded-xl">

        <div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto dark:text-gray-300 ">
                    <!-- Table header -->
                    <thead
                        class="text-xs font-semibold text-gray-500 uppercase border-b border-gray-100 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 dark:border-gray-700/60">
                        <tr>
                            <th class="w-px px-2 py-3 first:pl-5 last:pr-5 whitespace-nowrap"
                                v-if="$parent.available.massActions.length">
                                <div class="flex items-center">
                                    <label class="inline-flex" for="mass_action_select_all_records">
                                        <span class="sr-only">Select all</span>
                                        <input class="form-checkbox" :class="[
                                            $parent.applied.massActions.meta.mode === 'all' ? '' : (
                                                $parent.applied.massActions.meta.mode === 'partial' ? 'partial' : ''
                                            ),
                                        ]" type="checkbox" name="mass_action_select_all_records"
                                            id="mass_action_select_all_records"
                                            :checked="['all', 'partial'].includes($parent.applied.massActions.meta.mode)"
                                            @change="$parent.selectAllRecords" />
                                    </label>
                                </div>
                            </th>

                            <template v-for="column in $parent.available.columns">
                                <th class="px-2 py-3 first:pl-5 last:pr-5 whitespace-nowrap"
                                    :style="getColumnWidth(column) ?? ''">
                                    <div class="font-semibold cursor-pointer select-none hover:text-gray-800 dark:hover:text-white"
                                        :class="[(column.params && column.params.class) ? column.params.class : 'text-left',]"
                                        @click="$parent.sortPage(column)">
                                        {{ column.label }}
                                        <i class="pl-1"
                                            :class="[$parent.applied.sort.order === 'asc' ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up']"
                                            v-if="column.index == $parent.applied.sort.column"></i>
                                    </div>
                                </th>
                            </template>

                            <th class="px-2 py-3 first:pl-5 last:pr-5 whitespace-nowrap"
                                :style="getColumnWidth({ index: 'actions' }) ?? ''">
                                <div class="font-semibold text-left">Actions</div>
                            </th>
                        </tr>
                    </thead>

                    <!-- Table body -->
                    <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700/60"
                        v-if="$parent.records.length" id="sortable_list">
                        <tr v-for="record in $parent.records" class="sortable_row"
                            :key="record[$parent.meta.primary_column]" :node_id="record[$parent.meta.primary_column]"
                            :row_id="record['id']" :src_id="record['src_id']">

                            <!-- Mass Actions -->
                            <td class="w-px px-2 py-3 first:pl-5 last:pr-5 whitespace-nowrap"
                                v-if="$parent.available.massActions.length">
                                <div class="flex items-center">
                                    <label class="inline-flex"
                                        :for="`mass_action_select_record_${record[$parent.meta.primary_column]}`">
                                        <span class="sr-only">Select</span>
                                        <input class="form-checkbox" type="checkbox"
                                            :id="`mass_action_select_record_${record[$parent.meta.primary_column]}`"
                                            :name="`mass_action_select_record_${record[$parent.meta.primary_column]}`"
                                            :value="record[$parent.meta.primary_column]"
                                            v-model="$parent.applied.massActions.indices"
                                            @change="$parent.setCurrentSelectionMode" />
                                    </label>
                                </div>
                            </td>

                            <td v-for="column in $parent.available.columns"
                                class="px-2 py-3 first:pl-5 last:pr-5 whitespace-nowrap">
                                <div v-if="(column.control == 'position')"
                                    :class="[(column.params && column.params.class) ? column.params.class : '', 'flex']">
                                    <i class="fa-solid fa-grip-vertical drag_handler"
                                        :class="isDraggable('position') ? 'cursor-grab' : ''"></i>
                                </div>
                                <div v-else-if="(column.control == 'checkbox')"
                                    :class="[(column.params && column.params.class) ? column.params.class : '', 'flex']"
                                    class="items-center">
                                    <label :for="`${column.index}_switch_record_${record['id']}`" class="inline-flex">
                                        <input type="checkbox" :name="`${column.index}_switch_record_${record['id']}`"
                                            :id="`${column.index}_switch_record_${record['id']}`"
                                            v-model="record[column.index]" :true-value="1" :false-value="0"
                                            class="peer form-checkbox"
                                            @change="$parent.rowAction('toggleCheckbox', { record: record, column: column })">
                                        <span
                                            class="text-2xl rounded-md cursor-pointer icon-uncheckbox peer-checked:icon-checked peer-checked:text-blue-600">
                                        </span>
                                    </label>
                                </div>

                                <div v-else>
                                    <div class="font-medium"
                                        :class="[(column.params && column.params.class) ? column.params.class : '', '', isIdColumn(column) ? 'text-sky-600' : '']"
                                        v-html="getColumnValue(record, column)">
                                    </div>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="w-px px-2 py-3 first:pl-5 last:pr-5 whitespace-nowrap">
                                <div class="space-x-3" v-if="$parent.available.actions.length">
                                    <span
                                        class="cursor-pointer rhover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                        v-for="action in record.actions" @click="$parent.performAction(action)">
                                        <i class="fa-lg" :class="action.icon"></i>

                                    </span>
                                </div>
                            </td>

                        </tr>
                    </tbody>

                </table>
            </div>
        </div>
    </div>


</template>

<script>
// import NavButton from "../components/pagination/button.vue";
//import Paginator from "../components/pagination/paginator.vue";


export default {
    inject: ['vocab'],
    components: {
        //NavButton,
        //Paginator,
    },
    data() {
        return {
            paginator: this.$parent.applied.paginator,
            //position_sorting: false,
            sortableArea: null,
            handlers: [],
            ids: '',
        }

    },
    created() {
        console.log('table created ');
        //console.log('>>> created this.$parent.applied.massActions: ', this.$parent.applied.massActions);
    },
    mounted() {
        //console.log('table mounted ');
        this.trySetSortable();
    },
    updated() {
        //console.log('table updated ');
        this.trySetSortable();
    },
    computed: {
        gridColumns() {
            var w = [];
            if (this.$parent.available.massActions.length) {
                w.push('30px');
            }
            this.$parent.available.columns.forEach((column) => {
                if (column.params && column.params.width) {
                    w.push(column.params.width);
                } else {
                    w.push('minmax(50px, 1fr)');
                }
            });
            if (this.$parent.available.actions.length) {
                w.push('100px');
            }
            return 'grid-template-columns: ' + w.join(' ');
        }
    },
    methods: {
        isChecked(value) {
            console.log("isChecked value: ", value);
            return value == 1;
        },
        getColumnValue(record, column) {
            var value = record[column.index];
            var styles = '';
            if (column.index == 'id') {
                value = '#' + value;
                styles = styles = 'text-sky-600';
            } else {
                var a = column.index.split('.');
                if (a[1] == 'id') {
                    value = '#' + value + '<span class="text-gray-500">/' + record.id + '</span>';
                    styles = styles = 'text-sky-600';
                }
            }
            return value;
        },
        isIdColumn(column) {
            if (column.index == 'id') {
                return true;
            } else {
                var a = column.index.split('.');
                if (a[1] == 'id') {
                    return true;
                }
            }
            return false;
        },
        isDraggable(column) {
            if (this.$parent.meta.sortable_by_position && this.$parent.applied.sort?.column.includes(column)) {
                return true;
            }
            return false;
        },

        /*
         * ----------------------------------------------------------------------
         * ----------------- Position sorting methods ---------------------------
         * ----------------------------------------------------------------------
         */

        getRowsIds() {
            const rows = document.getElementsByClassName('sortable_row');
            var ids = [];
            var _id_n = (this.$parent.meta.has_variations && !this.$parent.applied.sort.column
                .includes('variation')) ? 'src_id' : 'row_id';
            //console.log('_id_n: ', _id_n);

            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];

                ids.push(row.getAttribute(_id_n));
            }
            //console.log('getRowsIds: ', ids);
            return ids;
        },
        removeSortingListeners() {
            //console.log('>>>>>>>>>> removeSortingListeners');
            if (this.sortableArea) {
                this.sortableArea.addEventListener(`dragstart`, this.onDragStart);
                this.sortableArea.addEventListener(`dragend`, this.onDragEnd);
                this.sortableArea.addEventListener(`dragover`, this.onDragOver);
            }
        },
        trySetSortable() {

            if (!this.$parent.meta.sortable_by_position) {
                return;
            }

            this.sortableArea = document.querySelector('#sortable_list');
            if (!this.sortableArea) {
                return;
            }
            this.handlers = this.sortableArea.querySelectorAll('.drag_handler');

            //if(this.sortableArea) {
            this.removeSortingListeners();
            //}

            if (!this.$parent.applied.sort?.column.includes('position')) {
                for (const handler of this.handlers) {
                    handler.draggable = false;
                }
                return;
            }

            for (const handler of this.handlers) {
                handler.draggable = true;
            }

            this.ids = this.getRowsIds().join(',');

            this.sortableArea.addEventListener(`dragstart`, this.onDragStart);
            this.sortableArea.addEventListener(`dragend`, this.onDragEnd);
            this.sortableArea.addEventListener(`dragover`, this.onDragOver);






            // this.ids = this.getRowsIds().join(',');
            // this.removeSortingListeners();

            // if(!this.$parent.meta.sortable_by_position){
            //     return;
            // }

            // const sortableArea = document.querySelector('#sortable_list');
            // const handlers = sortableArea.querySelectorAll('.drag_handler');

            // if (!this.$parent.applied.sort?.column.includes('position')) {
            //     for (const handler of handlers) {
            //         handler.draggable = false;
            //     }
            //     return;
            // } else {
            //     for (const handler of handlers) {
            //         handler.draggable = true;
            //     }
            // }

            // sortableArea.addEventListener(`dragstart`, this.onDragStart);
            // sortableArea.addEventListener(`dragend`, this.onDragEnd);

            // // sortableArea.addEventListener(`drop`, (evt) => {
            // //     console.log('drop', evt.target);
            // // });

            // sortableArea.addEventListener(`dragover`, (evt) => {
            //     evt.preventDefault();

            //     const activeElement = sortableArea.querySelector(`.selected`).closest('tr');
            //     //console.log('activeElement: ', activeElement);

            //     const currentElement = evt.target.closest('tr');
            //     //console.log('currentElement: ', currentElement);

            //     //console.log(activeElement.attributes.node_id.value, currentElement.attributes.node_id.value);

            //     this.isMoveable = activeElement.attributes.node_id.value !== currentElement.attributes.node_id.value;

            //     if (!this.isMoveable) {
            //         //console.log('not moveable');
            //         return;
            //     } else {
            //         //console.log('moveable');
            //     }

            //     const nextElement = (currentElement === activeElement.nextElementSibling) ?
            //         currentElement.nextElementSibling :
            //         currentElement;

            //     //console.log('nextElement: ', nextElement);

            //     sortableArea.insertBefore(activeElement, nextElement);

            //     //this.$parent.onRowsSortDragEnd();

            // });
        },
        onDragStart(evt) {
            evt.target.classList.add(`selected`);
            evt.dataTransfer.setDragImage(evt.target.closest('tr'), 90, 1);
        },
        onDragEnd(evt) {
            evt.target.classList.remove(`selected`);
            var ids = this.getRowsIds();
            if (ids.join(',') !== this.ids) {
                console.log('!========ids: ', ids, 'this.ids: ', this.ids);
                this.$parent.onRowsSortDragEnd(ids);
                this.ids = ids.join(',');
            }
        },
        onDragOver(evt) {
            evt.preventDefault();
            const activeElement = this.sortableArea.querySelector(`.selected`).closest('tr');
            //console.log('activeElement: ', activeElement);
            const currentElement = evt.target.closest('tr');
            //console.log('currentElement: ', currentElement);
            //console.log(activeElement.attributes.node_id.value, currentElement.attributes.node_id.value);
            this.isMoveable = activeElement.attributes.node_id.value !== currentElement.attributes.node_id.value;
            if (!this.isMoveable) {
                //console.log('not moveable');
                return;
            } else {
                //console.log('moveable');
            }
            const nextElement = (currentElement === activeElement.nextElementSibling) ?
                currentElement.nextElementSibling :
                currentElement;
            //console.log('nextElement: ', nextElement);
            this.sortableArea.insertBefore(activeElement, nextElement);
        },

        /*
         * ----------------------------------------------------------------------
         * -----END--------- Position sorting methods ---------------------------
         * ----------------------------------------------------------------------
         */

        getColumnWidth(column) {
            //console.log('getColumnWidth', column.index);
            switch (column.index) {
                // case 'id':
                //     return 'width: 100px;';
                case 'actions':
                    return 'width: 140px; text-align: center;';
                case 'active':
                    //console.log('active');
                    return 'width: 50px; text-align: center;';
                default:
                    //console.log('default', column.index, column.params);
                    if (column.params && column.params.width) {
                        //console.log('width: ' + column.params.width + 'px');
                        return 'width: ' + column.params.width;
                    } else {
                        return false;
                    }
            }
        },

        // navigation
        buttonCallback(direction) {
            //console.group('buttonCallback', direction, 'current_page: '+this.$parent.applied.paginator.current_page);
            if (direction == 'previous' && this.$parent.applied.paginator.current_page != 1) {
                console.log('callback prev');
                console.groupEnd();
                return () => this.$parent.changePage(direction);
            }
            if (direction == 'next' && this.$parent.applied.paginator.current_page < this.$parent.applied.paginator.last_page) {
                console.log('callback next');
                console.groupEnd();
                return () => this.$parent.changePage(direction);
            }
            //console.log('callback FALSE');
            //onsole.groupEnd();
            return false;
        },
        prevPage() {
            this.$parent.changePage('previous');
        },
        nextPage() {
            this.$parent.changePage('next');
        },
        isArrowDisabled(direction) {
            if (direction == 'prev' && this.paginator.current_page == 1) {
                return true;
            }
            if (direction == 'next' && this.paginator.current_page == this.paginator.last_page) {
                return true;
            }
        },

    }
};
</script>
