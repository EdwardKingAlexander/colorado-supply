<script setup>
import RepairHero from '@/Components/FrontEnd/RepairHero.vue'
import RepairValueProps from '@/Components/FrontEnd/RepairValueProps.vue'
import RepairCategories from '@/Components/FrontEnd/RepairCategories.vue'
import RepairProcess from '@/Components/FrontEnd/RepairProcess.vue'
import RepairRequestForm from '@/Components/FrontEnd/RepairRequestForm.vue'
import Footer from '@/Components/FrontEnd/Footer.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <AppLayout>
    <Head>
      <title>Industrial Repair Services | Colorado Supply & Procurement</title>
      <meta name="description" content="Colorado Supply & Procurement repairs industrial electronics, servo motors, AC & DC motors, and hydraulics & pneumatics for up to 50% less than new — free evaluation, no upfront cost, 2-year warranty.">
    </Head>

    <RepairHero />
    <RepairValueProps />
    <RepairCategories />
    <RepairProcess />
    <RepairRequestForm />
    <Footer />
  </AppLayout>
</template>
