<template>
  <div class="dc-app-layout">
    <Sidebar />
    <main class="dc-main-content">
      <slot />
    </main>
  </div>
</template>

<style>
.dc-app-layout {
  display: flex;
  min-height: 100vh;
  background: #0b1120;
  font-family: 'Inter', -apple-system, sans-serif;
}

.dc-main-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}
</style>