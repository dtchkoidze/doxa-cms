<template>
    <div class="max-w-7xl h-auto w-auto text-xl">
        <ul class="my-2 ">
            <li v-for="node in treeData" :key="node.id" :class="isParent(node) ? 'my-2' : 'mx-8'">
                <div class="flex items-center">
                    <i v-if="node.children.length" :class="[isExpanded(node), 'cursor-pointer']"
                        @click="expandNodeChildren(node)"></i>
                    <input type="checkbox"  :class="[hasAllChildrenChecked(node) ? 'text-sky-900' : '', 'border rounded-[4px] mx-3']" v-model="node.checked"
                        @change="handleCheckboxChange(node)" />
                    <span class="">
                        <i :class="[node.icon, 'text-purple-400 mr-1 text-[18px]']"></i>
                        {{ node.name }}
                    </span>
                </div>
                <ul v-if="node.expanded && node.children.length" class="">
                    <tree-view :tree="node.children"></tree-view>
                </ul>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        tree: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            treeData: this.tree,
            expandedNodes: JSON.parse(localStorage.getItem('expanded-nodes')) || [], 
        }
    },
    methods: {
        expandNodeChildren(node) {
            node.expanded = !node.expanded;

            if (node.expanded) {
                if (!this.expandedNodes.includes(node.key)) {
                    this.expandedNodes.push(node.key);
                }
            } else {
                this.expandedNodes = this.expandedNodes.filter(key => key !== node.key);
            }

            localStorage.setItem('expanded-nodes', JSON.stringify(this.expandedNodes));
        },

        isExpanded(node) {
            return node.expanded ? 'fas fa-chevron-right ml-2' : 'fas fa-chevron-down';
        },

        isParent(node) {
            return node.children && node.children.length > 0;
        },

        isChild(node) {
            return node.parent;
        },

        hasAllSiblingsChecked(node) {
            if (this.isChild(node)) {
                let parentNode = node.parent;
                let checkedChildren = parentNode.children.filter(child => child.checked);
                let allChildren = parentNode.children.length;
                parentNode.checked = checkedChildren.length === allChildren;
            }
        },

        hasAllChildrenChecked(parentNode) {
            if (!parentNode || !Array.isArray(parentNode.children) || parentNode.children.length === 0) {
                return false;
            }

            if (this.isParent(parentNode)) {
                console.log("is truly parent");
                console.log(parentNode.key);
                console.log(parentNode.children.every(child => child.checked));
                return parentNode.children.every(child => child.checked);
            }
            console.log('false returned');
            return false;
        },
        
        handleCheckboxChange(node) {
            if (!node) return;
            if (this.isParent(node)) {
                node.children.forEach((child) => {
                    child.checked = node.checked;
                    this.$emitter.emit('node-checked', child);
                })
            }
            this.$emitter.emit('node-checked', node);
        },

    },

    mounted() {

    },

    created() {
        this.treeData.forEach((node) => {
            node.expanded = this.expandedNodes.includes(node.key);
            node.children = [];
        });

        this.treeData.forEach((node) => {
            let nodeKey = node.key;
            let parentKey = nodeKey.split('.')[0];

            if (parentKey !== nodeKey) {
                let parentNode = this.treeData.find(n => n.key === parentKey);
                if (parentNode) {
                    parentNode.children.push(node);
                    this.treeData = this.treeData.filter(n => n.key !== nodeKey);
                }
            }
        });

        this.treeData.forEach((node) => {
            if (this.isParent(node)) {
                node.children.forEach((child) => {
                    child.parent = node;
                })
            }
        })
    }
}

</script>