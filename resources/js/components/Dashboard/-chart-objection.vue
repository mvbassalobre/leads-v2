<template>
    <div class="col-12 dash-card flex-fill">
        <loading-shimmer :loading="loading" :h="120" class="h-100">
            <div class="card shadow h-100">
                <div class="container py-3">
                    <div class="d-flex flex-column">
                        <b class="mb-1">Classificação de Leads por Objeção </b>
                        <small class="text-muted mb-3">Distribuição dos leads criados no periodo do filtro em status</small>
                        <pie-chart :data="chart" v-if="chart.length" legend="right" />
                        <div class="py-5 d-flex flex-row align-items-center justify-content-center" v-else>
                            <small class="text-muted">Não existe conteúdo para este filtro</small>
                        </div>
                    </div>
                </div>
            </div>
        </loading-shimmer>
    </div>
</template>
<script>
import { mapActions, mapGetters } from "vuex";
export default {
    data() {
        return {
            loading: true,
            timeout: null,
            chart: {}
        };
    },
    computed: {
        ...mapGetters("dashboard", ["filter"])
    },
    watch: {
        filter: {
            handler() {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.loading = true;
                    this.getData();
                });
            },
            deep: true
        }
    },
    created() {
        this.getData();
    },
    methods: {
        ...mapActions("dashboard", ["getDashboardContent"]),
        getData() {
            this.getDashboardContent({ action: "getLeadPerObjection" }).then(data => {
                this.chart = data.map(x => [x.objection, x.qty]);
                this.loading = false;
            });
        }
    }
};
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
