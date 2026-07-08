import { createRouter, createWebHistory } from 'vue-router'
import HomeForm from '../components/HomeForm.vue'
import UserLogin from '../components/UserLogin.vue'
import UserManager from '../components/UserManager.vue'
import ArchivObjekt from '../components/ArchivObjekt.vue'
import ArchivSuche from '@/components/ArchivSuche.vue'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'Home',
      component: HomeForm,
      meta: { requiresAuth: true },
    },
    {
      path: '/archivObjekt',
      name: 'Archivobjekt',
      component: ArchivObjekt,
      meta: { requiresAuth: true },
    },
    {
      path: '/archivSuche',
      name: 'Archivsuche',
      component: ArchivSuche,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'Login',
      component: UserLogin,
    },
    {
      path: '/UserManager',
      name: 'Benutzer verwalten',
      component: UserManager,
      meta: { requiresAuth: true },
    },
  ],
})
router.beforeEach((to) => {
  const isLoggedIn = !!sessionStorage.getItem('sessionId')

  if (to.meta.requiresAuth && !isLoggedIn) {
    return '/login'
  }

  if (to.path === '/login' && isLoggedIn) {
    return '/'
  }
  return true
})

export default router
