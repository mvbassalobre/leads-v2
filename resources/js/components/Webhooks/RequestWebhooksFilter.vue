<template>
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <el-radio-group v-model="filter.visibility" class="mr-3">
                <el-radio-button label="hidden">Ocultos</el-radio-button>
                <el-radio-button label="visible">Visíveis</el-radio-button>
            </el-radio-group>
            <el-radio-group v-model="filter.status">
                <el-radio-button label="all">Todos Status</el-radio-button>
                <el-radio-button label="waiting">Aguardando</el-radio-button>
                <el-radio-button label="approved">Aprovado</el-radio-button>
            </el-radio-group>
        </div>
        <div class="col-12 my-3">
            <el-input placeholder="Filtro de conteúdo" suffix-icon="el-input__icon el-icon-search" v-model="filter.filter" />
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            filter: {
                status: this.$getUrlParams().request_status || "all",
                visibility: this.$getUrlParams().visibility || "visible",
                filter: this.$getUrlParams().request_filter || "",
                page: this.$getUrlParams().requests_page || 1,
                timeout: null,
            },
        };
    },
    watch: {
        filter: {
            deep: true,
            handler() {
                clearInterval(this.timeout);
                this.timeout = setInterval(() => {
                    this.makeFilter();
                    clearInterval(this.timeout);
                }, 500);
            },
        },
    },
    computed: {
        route() {
            return `${window.location.pathname}?request_status=${this.filter.status}&visibility=${this.filter.visibility}&request_filter=${this.filter.filter}&requests_page=${this.filter.page}`;
        },
    },
    methods: {
        makeFilter() {
            this.$loading({ text: "Aguarde ..." });
            window.location.href = this.route;
        },
    },
};
</script>
