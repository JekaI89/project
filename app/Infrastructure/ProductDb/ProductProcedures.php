<?php

namespace App\Infrastructure\ProductDb;

/**
 * Catalog of stored procedures as called from Delphi (dmGet.dfm / forms).
 * Single place to adjust names/params if DB version differs.
 */
final class ProductProcedures
{
    public const STOCK_LIST = 'goods_nal_sp_get_listb';
    public const GOODS_FIND = 'goods_catalog_sp_find';
    public const GOODS_SAVE = 'goods_catalog_sp_save';
    public const GOODS_QMIN_LIST = 'goods_catalog_qmin_sp_getlist';
    public const GOODS_REPLACE = 'goods_sp_replace';
    public const GOODS_DEL = 'goods_sp_dels';
    public const HISTORY_ADD = 'history_sp_add';
    public const MOVE_DOC_ADD = 'goods_nal_move_doc_sp_add';
    public const MOVE_DOC_ADD_ID = 'goods_nal_move_doc_sp_add_id';
    public const MOVES_REPORT_DOCS = 'x_otchet_docs';
    public const INV_FIND = 'inv_sp_finds';
    public const INV_SAVE = 'inv_sp_save';
    public const INV_SET = 'inv_sp_set';
    public const ORDERS_LIST = 'orders_clients_sp_get_list';
    public const ORDERS_SET_STATUS = 'orders_clients_sp_set_status';
    public const CONFIG_RUN = 'config_sp_run';
    public const CONFIG_LOAD = 'config_sp_load';
    public const CONFIG_SAVE = 'config_sp_save';
    public const USERS_LIST = 'users_sp_get_list';
    public const USERS_SAVE = 'users_sp_save';
    public const USERS_SET_ACTIVE = 'users_sp_set_active';
    public const USERS_COPY = 'users_sp_copy';
    public const USERS_PRAVO_LIST = 'users_pravo_sp_get_list_user_id';
    public const USERS_PRAVO_SET = 'users_pravo_sp_set';
    public const PLAN_LISTS = 'calendar_plan_list_sp_load_list';
    public const PLAN_LOAD = 'calendar_plan_sp_load';
    public const PLAN_SAVE = 'calendar_plan_sp_save';
    public const PLAN_SET_ST = 'calendar_plan_sp_set_st';
    public const TASK_FIND = 'task_sp_find';
    public const TASK_LOAD = 'task_sp_load';
    public const TASK_LOAD_NOTE = 'task_sp_load_note';
    public const TASK_LOAD_WATCHER = 'task_sp_load_watcher';
    public const TASK_SAVE = 'task_sp_save';
    public const TASK_SET_STATUS = 'task_sp_set_status';
    public const TASK_NOTE_SAVE = 'task_node_sp_save';
    public const CRITICAL_NAME_LOAD = 'goods_critical_name_sp_load';
    public const CRITICAL_NAME_SAVE = 'goods_critical_name_sp_save';
    public const CRITICAL_NAME_COPY = 'goods_critical_name_sp_copy';
    public const SYS_COPY = 'x_sys_copy';
    public const FINANCE_LIST = 'finance_sp_get_list';
    public const FINANCE_SAVE = 'finance_sp_save';
    public const FINANCE_DEL = 'finance_sp_del';
    public const FINANCE_SET_CLOSE = 'finance_sp_set_close';
    public const PURCHASE_LIST = 'store_sp_get_list';
    public const PURCHASE_SAVE = 'store_sp_save';
    public const PURCHASE_STATUS_SET = 'store_status_sp_set';
    public const PURCHASE_STATUS_SET_DATE = 'store_status_sp_set_date';
    public const PURCHASE_CLOSE = 'store_sp_close';
    public const PURCHASE_SPEC_LIST = 'store_spec_sp_getlist';
    public const PURCHASE_ADD = 'store_sp_add';
    public const ROUTING_FIND_LIST = 'routing_sp_find_list';
    public const ROUTING_SPEC_LOAD = 'routing_spec_sp_load';
    public const ROUTING_GOODS_GET = 'routing_goods_sp_get';
    public const ROUTING_SET_ACTIV = 'routing_sp_set_activ';
    public const ROUTING_COPY = 'routing_sp_copy';
}
