<template>
    <nav class="flex items-stretch" role="navigation" aria-label="Navigation">
        <div class="mr-2">
            <nav-button :button="{ type: 'prev', title: 'Previous', callback: _callback('previous') }"></nav-button>
            <!-- <template v-if="isArrowDisabled('prev')">
                        <span class="nav_arrow _na">
                            <nav-arrow :src="{ type: 'prev', title: 'Previous' }"></nav-arrow>
                        </span>
                    </template> -->
        </div>
        <ul class="inline-flex  -space-x-px text-sm font-medium rounded-lg shadow-sm">
            <li v-for="(page, index) in getPages()" :key="index">
                <nav-page :page="{ number: page, callback: _callback(page) }" :classes="pageClasses(page)"></nav-page>
            </li>
        </ul>
        <div class="ml-2">
            <nav-button :button="{ type: 'next', title: 'Next', callback: _callback('next') }"></nav-button>
        </div>
    </nav>
</template>

<script>
import NavButton from "./button.vue";
import NavPage from "./page.vue";

export default {
    props: [
        //'paginator',
        'parent',
        //'callback',
    ],
    components: {
        NavButton,
        NavPage,
    },
    // data() {
    //     return {
    //        // _paginator: this.paginator,
    //         //_parent: this.parent,
    //     }
    // },
    mounted() {
        //console.log("paginator mounted");
        this.getPages();
    },
    methods: {
        _callback(directionOrNumber) {
            //console.log('_callback', directionOrNumber, typeof directionOrNumber, 'current_page: '+this.parent.applied.paginator.current_page);

            if (typeof directionOrNumber === 'string') {
                if (directionOrNumber === 'previous') {
                    if (this.parent.applied.paginator.current_page == 1) {
                        return false;
                    }
                } else if (directionOrNumber === 'next') {
                    if (this.parent.applied.paginator.current_page == this.parent.applied.paginator.last_page) {
                        return false;
                    }
                } else {
                    return false;
                }
            } else if (typeof directionOrNumber === 'number') {
                if (directionOrNumber == this.parent.applied.paginator.current_page) {
                    return false;
                }
            } else {
                //console.warn('Invalid Input Provided: ' + directionOrPageNumber);
                return false;
            }

            return () => this.parent.changePage(directionOrNumber);
        },
        pageClasses(page) {
            var classes = ['nav_page'];
            if (page == 1) {
                classes.push('rounded-l-lg');
            }
            if (page == this.parent.applied.paginator.last_page) {
                classes.push('rounded-r-lg');
            }

            if (page == this.parent.applied.paginator.current_page) {
                classes.push('_na');
            } else {
                if (page == '...') {
                    classes.push('points');
                } else {
                    classes.push('_link');
                }
            }

            return classes.join(' ');
        },

        getPages() {
            let total_page = this.parent.applied.paginator.last_page;
            let current_page = this.parent.applied.paginator.current_page;
            let each_side = 1;

            var start_page, end_page;

            if (total_page <= (2 * each_side) + 5) {
                // in this case, too few pages, so display them all
                start_page = 1;
                end_page = total_page;
            } else {
                if (current_page <= (each_side + 3)) {
                    //in this case, current_page is too close to the beginning
                    start_page = 1;
                    end_page = (2 * each_side) + 3;
                } else {
                    if (current_page >= total_page - (each_side + 2)) {
                        start_page = total_page - (2 * each_side) - 2;
                        end_page = total_page;
                    } else {
                        // regular case
                        start_page = current_page - each_side
                        end_page = current_page + each_side
                    }
                }
            }

            var re = [];
            if (start_page > 1) {
                re.push(1);
            }
            if (start_page > 2) {
                re.push('...');
            }
            for (var x = start_page; x <= end_page; x++) {
                re.push(x);
            }
            if (end_page < total_page - 1) {
                re.push('...');
            }
            if (end_page < total_page) {
                re.push(total_page);
            }
            //console.log('pgpages', re);

            return re;


            //         if total_page <= (2*each_side)+5
            //     # in this case, too few pages, so display them all
            //     start_page = 1
            //     end_page = total_page
            // else if curr_page<=each_side+3
            //     # in this case, curr_page is too close to the beginning
            //     start_page = 1
            //     end_page = (2*each_side)+3
            // else if curr_page >= total_page - (each_side+2)
            //     # in this case, curr_page is too close to the end
            //     start_page = total_page - (2*each_side) - 2
            //     end_page = total_page
            // else
            //     # regular case
            //     start_page = curr_page - each_side
            //     end_page = curr_page + each_side
            // return_me = []
            // if start_page> 1
            //     return_me.push "1"
            // if start_page>2
            //     return_me.push "..."
            // for x in [start_page..end_page]
            //     return_me.push x
            // if end_page<total_page-1
            //     return_me.push "..."
            // if end_page<total_page
            //     return_me.push total_page
            // return return_me

        },
    },
};
</script>
