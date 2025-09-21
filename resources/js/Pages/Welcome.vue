<script setup>
import Navigation from '@/Components/Navigation.vue'
import Header from '@/Components/FrontEnd/Header.vue'
import Hero from '@/Components/FrontEnd/Hero.vue'
import About from '@/Components/FrontEnd/About.vue'
import Capabilities from '@/Components/FrontEnd/Capabilities.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Contact from '@/Components/FrontEnd/Contact.vue'
import Compliance from '@/Components/FrontEnd/Compliance.vue'
import IndustriesServed from '@/Components/FrontEnd/IndustriesServed.vue'
import Footer from '@/Components/FrontEnd/Footer.vue'

defineProps({
  appName: String,
})
</script>

<template>
  <AppLayout :appName="appName" >
    
    <Hero />
    <About />
    <Capabilities />
    <Contact />
    <Compliance />
    <IndustriesServed />
    <Footer />
  </AppLayout>

</template>