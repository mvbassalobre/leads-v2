<template>
    <div class="col-12 dash-card flex-fill" style="min-height: 400px">
        <loading-shimmer :loading="loading" :h="400" class="h-100">
            <div class="card shadow h-100">
                <div class="container py-3">
                    <div class="d-flex flex-column">
                        <b class="mb-1">
                            <span class="el-icon-s-comment mr-2" />
                            Top 10 - Atendimentos por Departamento
                        </b>
                        <small class="text-muted mb-3"> Saiba qual sãos os departamentos mais produtivos considerando o filtro </small>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="f-12">Nome</th>
                                    <th class="f-12">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in rows" :key="i">
                                    <td class="f-12">{{ medals[i] }}</td>
                                    <td class="f-12">{{ row.department }}</td>
                                    <td class="f-12">{{ row.qty }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </loading-shimmer>
    </div>
</template>
<script>
import { mapActions, mapGetters } from 'vuex'
export default {
    data() {
        return {
            loading: true,
            timeout: null,
            rows: [],
        }
    },
    computed: {
        ...mapGetters("dashboard",["filter","medals"]),
    },
    created() {
        this.getData()
    },
    watch: {
        filter: {
            handler(val) {
                clearTimeout(this.timeout)
                this.timeout = setTimeout(() => {
                    this.loading = true
                    this.getData()
                })
            },
            deep: true,
        },
    },
    methods: {
        ...mapActions('dashboard', ['getDashboardContent']),
        getData() {
            this.getDashboardContent({ action: 'getRankingDepartments' }).then((data) => {
                this.rows = data
                this.loading = false
            })
        },
    },
}
</script>
<style lang="scss" scoped>
.dash-card {
    .number {
        font-weight: 600;
        font-size: 30px;
    }
    .trend {
        margin-bottom: 15px;
        margin-left: 10px;
        font-size: 12px;
    }
    .description {
        font-size: 11px;
        color: gray;
    }
}
</style>