<template>
  <div class="main-content">
    <breadcumb :page="$t('CashUser')" :folder="$t('Cashs')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading && !editmode">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="cash_users"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{ enabled: true, placeholder: $t('Search_this_table') }"
        :pagination-options="{ enabled: true, mode: 'records', nextLabel: 'next', prevLabel: 'prev' }"
        styleClass="table-hover tableOne vgt-table"
      >
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button @click="New_cash_user()" class="btn-rounded" variant="btn btn-primary btn-icon m-1">
            <i class="i-Add"></i> {{ $t('Add') }}
          </b-button>
        </div>
        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_cash_user(props.row)" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <b-card class="wrapper" v-if="!isLoading && editmode">
      <validation-observer ref="Create_CashUser">
        <b-form @submit.prevent="Submit_CashUser">
          <b-row>
            <b-col cols="12" md="6" lg="4">
              <validation-provider name="warehouse" :rules="{ required: true }">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('warehouse') + ' *'">
                  <v-select
                    :class="{ 'is-invalid': !!errors.length }"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="selected_warehouse"
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Warehouse')"
                    :options="warehouses.map(row => ({ label: row.name, value: row.id }))"
                  />
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col cols="12" md="6" lg="4">
              <validation-provider name="cash" :rules="{ required: true }">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('Cashs') + ' *'">
                  <v-select
                    :class="{ 'is-invalid': !!errors.length }"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="selected_cash"
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Cash')"
                    :options="cashs.map(row => ({ label: row.name, value: row.id }))"
                  />
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <b-col md="12" class="mt-3">
              <b-button variant="secondary" @click="back()" :disabled="SubmitProcessing">
                <i class="i-Arrow-Left me-2 font-weight-bold"></i> {{ $t('back') }}
              </b-button>
              <b-button variant="primary" type="submit" :disabled="SubmitProcessing">
                <i class="i-Yes me-2 font-weight-bold"></i> {{ $t('submit') }}
              </b-button>
              <div v-once class="typo__p" v-if="SubmitProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
            </b-col>
            <b-col cols="12" md="6" class="mt-4">
              <b-table
                :items="associated_cash_boxes"
                :fields="cash_box_fields"
                striped
                hover
                responsive
                show-empty
                small
              >
                <template #empty>
                  <h6 class="text-center text-muted">{{ $t('No_associated_cash_boxes') }}</h6>
                </template>
                <template #cell(actions)="row">
                  <b-button variant="danger" size="sm" @click="deleteCashBox(row.item)" :title="$t('Delete')">
                    <i class="i-Close"></i> {{ $t('Delete') }}
                  </b-button>
                </template>
              </b-table>
            </b-col>
          </b-row>
        </b-form>
      </validation-observer>
    </b-card>
  </div>
</template>

<script>
import NProgress from "nprogress";

export default {
  metaInfo: { title: "CashUser" },
  data() {
    return {
      isLoading: true,
      SubmitProcessing: false,
      serverParams: { columnFilters: {}, sort: { field: "username", type: "asc" }, page: 1, perPage: 10 },
      totalRows: "",
      search: "",
      limit: "10",
      cash_users: [],
      editmode: false,
      cash_user: { id: "", user_id: "", cash_id: null, warehouse_id: null },
      selected_warehouse: "",
      selected_cash: "",
      cashs: [],
      warehouses: [],
      associated_cash_boxes: [],
      cash_box_fields: [{ key: "cash_name", label: "Cashs" }, { key: "actions", label: "Actions" }]
    };
  },
  watch: {
    selected_warehouse(val) {
      if (val) {
        this.cash_user.warehouse_id = val;
        this.getCashsByWarehouse();
      }
    },
    selected_cash(val) {
      if (val) this.cash_user.cash_id = val;
    }
  },
  computed: {
    columns() {
      return [
        { label: this.$t("Code"), field: "username", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("Name"), field: "email", tdClass: "text-left", thClass: "text-left" },
        { label: this.$t("State"), field: "statut", tdClass: "text-left", thClass: "text-left", formatFn: (v) => (v === 1 ? "Active" : "Inactive") },
        { label: this.$t("Action"), field: "actions", html: true, tdClass: "text-right", thClass: "text-right", sortable: false }
      ];
    }
  },
  methods: {
    updateParams(newProps) { this.serverParams = Object.assign({}, this.serverParams, newProps); },
    onPageChange({ currentPage }) { if (this.serverParams.page !== currentPage) { this.updateParams({ page: currentPage }); this.Get_CashUser(currentPage); } },
    onPerPageChange({ currentPerPage }) { if (this.limit !== currentPerPage) { this.limit = currentPerPage; this.updateParams({ page: 1, perPage: currentPerPage }); this.Get_CashUser(1); } },
    onSortChange(params) { this.updateParams({ sort: { type: params[0].type, field: params[0].field } }); this.Get_CashUser(this.serverParams.page); },
    onSearch(value) { this.search = value.searchTerm; this.Get_CashUser(this.serverParams.page); },
    getValidationState({ dirty, validated, valid = null }) { return dirty || validated ? valid : null; },
    Submit_CashUser() {
      this.$refs.Create_CashUser.validate().then((success) => {
        if (!success) this.makeToast("danger", this.$t("Please_fill_the_form_correctly"), this.$t("Failed"));
        else this.Create_CashUser();
      });
    },
    makeToast(variant, msg, title) { this.$root.$bvToast.toast(msg, { title, variant, solid: true }); },
    New_cash_user() { this.reset_Form(); this.editmode = false; this.$bvModal.show("New_CashUser"); },
    Edit_cash_user(row) {
      this.cash_user.user_id = row.id;
      this.GetWarehousesForUser(row.id);
      this.associated_cash_boxes = (row.cash_user || []).map((cu) => ({ ...cu, cash_name: cu.cash ? cu.cash.name : cu.cash_name || "" }));
      this.editmode = true;
    },
    Get_CashUser(page) {
      NProgress.start();
      axios.get("cash_user?page=" + page + "&SortField=" + this.serverParams.sort.field + "&SortType=" + this.serverParams.sort.type + "&search=" + this.search + "&limit=" + this.limit)
        .then((r) => {
          this.cash_users = r.data.users || [];
          this.totalRows = r.data.totalRows || 0;
          NProgress.done();
          this.isLoading = false;
        })
        .catch(() => { NProgress.done(); this.isLoading = false; });
    },
    Create_CashUser() {
      this.SubmitProcessing = true;
      axios.post("cash_user", this.cash_user).then(() => {
        this.SubmitProcessing = false;
        this.makeToast("success", this.$t("Assigment"), this.$t("Success"));
        this.getCashUsers();
      }).catch((e) => {
        this.SubmitProcessing = false;
        this.makeToast("danger", (e.response && e.response.data && e.response.data.message) || this.$t("InvalidData"), this.$t("Failed"));
      });
    },
    reset_Form() { this.selected_warehouse = null; this.selected_cash = null; this.cash_user = { id: "", user_id: null, cash_id: null, warehouse_id: null }; },
    GetWarehousesForUser(userId) {
      axios.get("cash_user/warehouses_for_user", { params: { user_id: userId } })
        .then((r) => { this.warehouses = r.data || []; })
        .catch(() => { this.warehouses = []; });
    },
    getCashsByWarehouse() {
      if (!this.cash_user.warehouse_id) { this.cashs = []; return; }
      axios.get("cash/warehouse/" + this.cash_user.warehouse_id)
        .then((r) => { this.cashs = r.data.cashs || []; })
        .catch(() => { this.cashs = []; });
    },
    getCashUsers() {
      axios.get("cash_users", { params: { user_id: this.cash_user.user_id } })
        .then((r) => { this.associated_cash_boxes = (r.data.cash_users || []).map((cu) => ({ ...cu, cash_name: cu.cash ? cu.cash.name : cu.cash_name || "" })); })
        .catch(() => {});
    },
    back() { Fire.$emit("Event_CashUser"); },
    deleteCashBox(item) {
      this.$bvModal.msgBoxConfirm(this.$t("Are_you_sure_you_want_to_delete_this_item"), {
        title: this.$t("Confirm_Delete"), size: "sm", buttonSize: "sm", okVariant: "danger", okTitle: this.$t("Yes"), cancelTitle: this.$t("No"), footerClass: "p-2", hideHeaderClose: false, centered: true
      }).then((value) => {
        if (value) {
          axios.delete("cash_user/" + item.id).then(() => {
            const idx = this.associated_cash_boxes.findIndex((b) => b.id === item.id);
            if (idx !== -1) this.associated_cash_boxes.splice(idx, 1);
          }).catch(() => this.$swal(this.$t("Delete.Failed"), this.$t("Delete.Therewassomethingwronge"), "warning"));
        }
      }).catch(() => {});
    }
  },
  created() {
    this.Get_CashUser(1);
    Fire.$on("Event_CashUser", () => { setTimeout(() => { this.editmode = false; this.Get_CashUser(this.serverParams.page); this.$bvModal.hide("New_CashUser"); }, 500); });
    Fire.$on("Delete_CashUser", () => { setTimeout(() => this.Get_CashUser(this.serverParams.page), 500); });
  }
};
</script>
