
<template>
  <svg v-if="place === 1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    <g id="SVGRepo_iconCarrier">
      <path
        d="M12.0002 16C6.24021 16 5.21983 10.2595 5.03907 5.70647C4.98879 4.43998 4.96365 3.80673 5.43937 3.22083C5.91508 2.63494 6.48445 2.53887 7.62318 2.34674C8.74724 2.15709 10.2166 2 12.0002 2C13.7837 2 15.2531 2.15709 16.3771 2.34674C17.5159 2.53887 18.0852 2.63494 18.5609 3.22083C19.0367 3.80673 19.0115 4.43998 18.9612 5.70647C18.7805 10.2595 17.7601 16 12.0002 16Z"
        stroke="#1C274C" stroke-width="1.5"></path>
      <path
      d="M11 8L12.5 6.5V10.5" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      </path>
      <path
        d="M19 5L19.9486 5.31621C20.9387 5.64623 21.4337 5.81124 21.7168 6.20408C22 6.59692 22 7.11873 21.9999 8.16234L21.9999 8.23487C21.9999 9.09561 21.9999 9.52598 21.7927 9.87809C21.5855 10.2302 21.2093 10.4392 20.4569 10.8572L17.5 12.5"
        stroke="#1C274C" stroke-width="1.5"></path>
      <path
        d="M4.99994 5L4.05132 5.31621C3.06126 5.64623 2.56623 5.81124 2.2831 6.20408C1.99996 6.59692 1.99997 7.11873 2 8.16234L2 8.23487C2.00003 9.09561 2.00004 9.52598 2.20723 9.87809C2.41441 10.2302 2.79063 10.4392 3.54305 10.8572L6.49994 12.5"
        stroke="#1C274C" stroke-width="1.5"></path>
      <path d="M12 16V19" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path>
      <path 
        d="M15.5 22H8.5L8.83922 20.3039C8.93271 19.8365 9.34312 19.5 9.8198 19.5H14.1802C14.6569 19.5 15.0673 19.8365 15.1608 20.3039L15.5 22Z"
        stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
      <path d="M18 22H6" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path>
    </g>
  </svg>
  <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    <g id="SVGRepo_iconCarrier">
      <circle cx="12" cy="9" r="7" stroke="#1C274C" stroke-width="1.5"></circle>
      <path 
        d="M7.35111 15L6.71424 17.323C6.0859 19.6148 5.77173 20.7607 6.19097 21.3881C6.3379 21.6079 6.535 21.7844 6.76372 21.9008C7.41635 22.2331 8.42401 21.7081 10.4393 20.658C11.1099 20.3086 11.4452 20.1339 11.8014 20.0959C11.9335 20.0818 12.0665 20.0818 12.1986 20.0959C12.5548 20.1339 12.8901 20.3086 13.5607 20.658C15.576 21.7081 16.5837 22.2331 17.2363 21.9008C17.465 21.7844 17.6621 21.6079 17.809 21.3881C18.2283 20.7607 17.9141 19.6148 17.2858 17.323L16.6489 15"
        stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path>
    </g>
    <g transform="translate(1,-1)" v-if="place === 2">
      <path  d="M9.2 7.5C9 7.5 10.5 6 12 6.5C13.5 7 13 8.5 12.5 9.5L9 13H13.2" stroke="#1C274C" stroke-width="1.5"
        stroke-linecap="round" stroke-linejoin="round"></path>

    </g>
    <g  transform="scale(0.8) translate(4,0) " v-else-if="place === 3">
      <path   d="M9 7C9 7 10.5 6 12 7C13.5 8 13.5 9 12 10C10.5 11 9 11 9 11M9 11C9 11 10.5 12 12 12C13.5 13 13.5 14 12 15C10.5 16 9 16 9 16" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>

    </g>
  </svg>
</template>

  
<script setup lang="ts">


defineProps({
  place: {
    type: Number,
    required: true,
  },
})
</script>

<style scoped>
.place-1 path {
  fill: #FFD700;
}

.place-2 path {
  fill: #C0C0C0;
}

.place-3 path {
  fill: #CD7F32;
}
</style>