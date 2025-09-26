<script setup>
import Hero from '@/Components/FrontEnd/Hero.vue'
import About from '@/Components/FrontEnd/About.vue'
import Capabilities from '@/Components/FrontEnd/Capabilities.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Contact from '@/Components/FrontEnd/Contact.vue'
import Compliance from '@/Components/FrontEnd/Compliance.vue'
import IndustriesServed from '@/Components/FrontEnd/IndustriesServed.vue'
import Footer from '@/Components/FrontEnd/Footer.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  appName: String,
})
</script>

<template>
  <AppLayout :appName="appName" >
     <Head>
    <title>Colorado Supply & Procurement | Trusted Industrial Supplier for Government Contracts</title>
<meta name="description" content="Colorado Supply & Procurement provides reliable industrial supply chain services to federal, state, and local agencies. Over 10 years of experience delivering quality products, competitive pricing, and on-time performance.">
<meta name="author" content="Colorado Supply & Procurement LLC">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://cogovsupply.com/">
<meta property="og:title" content="Colorado Supply & Procurement | Trusted Industrial Supplier for Government Contracts">
<meta property="og:description" content="Reliable industrial supply chain partner for federal, state, and local government agencies. Over 10 years of experience, competitive pricing, and on-time delivery.">
<meta property="og:image" content="https://cogovsupply.com/images/og-image.jpg"> <!-- You should upload this image -->

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://cogovsupply.com/">
<meta name="twitter:title" content="Colorado Supply & Procurement | Trusted Government Supplier">
<meta name="twitter:description" content="Reliable government-focused procurement and industrial supply chain services. Experienced, dependable, on-time.">
<meta name="twitter:image" content="https://cogovsupply.com/images/og-image.jpg"> <!-- Same image -->

<!-- Favicon -->
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
  </Head>
    <Hero />
    <About />
    <Capabilities />
    <Contact />
    <Compliance />
    <IndustriesServed />
    <Footer />
  </AppLayout>

</template>