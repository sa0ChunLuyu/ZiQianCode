<script setup>
/**
 * name：
 * user：sa0ChunLuyu
 * date：2024年10月18日 16:18:16
 */
</script>
<template>
  <div>
    <el-card>
      <template #header>QD数据库</template>
      <div>
        <QuickDatabase database="quick_databases">
          <template v-slot:header>
            <div class="mb-2">
              <el-alert title="以下配置轻易勿动！请在您已完全了解并知晓其配置具体含义之后再进行操作！！！" type="error" :closable="false"/>
            </div>
          </template>
        </QuickDatabase>
      </div>
    </el-card>
  </div>
</template>
<style scoped></style>
<route>
{"meta":{"title":"QD数据库"}}
</route>
