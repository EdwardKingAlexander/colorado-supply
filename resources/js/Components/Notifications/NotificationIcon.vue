<script setup>
import { computed } from 'vue'
import {
  ArchiveBoxIcon,
  ArrowUturnLeftIcon,
  BellIcon,
  BuildingStorefrontIcon,
  CheckCircleIcon,
  ClockIcon,
  CreditCardIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  HomeIcon,
  ShoppingBagIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

// Maps the server's heroicon name (NotificationType::icon()) to a component.
const props = defineProps({ name: { type: String, default: null } })

const ICONS = {
  'heroicon-o-archive-box': ArchiveBoxIcon,
  'heroicon-o-arrow-uturn-left': ArrowUturnLeftIcon,
  'heroicon-o-building-storefront': BuildingStorefrontIcon,
  'heroicon-o-check-circle': CheckCircleIcon,
  'heroicon-o-clock': ClockIcon,
  'heroicon-o-credit-card': CreditCardIcon,
  'heroicon-o-document-text': DocumentTextIcon,
  'heroicon-o-exclamation-triangle': ExclamationTriangleIcon,
  'heroicon-o-home': HomeIcon,
  'heroicon-o-shopping-bag': ShoppingBagIcon,
  'heroicon-o-truck': TruckIcon,
  'heroicon-o-x-circle': XCircleIcon,
}

const icon = computed(() => ICONS[props.name] ?? BellIcon)
</script>

<template>
  <component :is="icon" aria-hidden="true" />
</template>
