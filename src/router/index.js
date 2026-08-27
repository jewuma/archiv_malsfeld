import { createRouter, createWebHistory } from 'vue-router'
import ArchivEingang from '@/components/ArchivEingang.vue'
import ArchivSuche from '@/components/ArchivSuche.vue'
import HomeForm from '../components/HomeForm.vue'
import QuellenMananger from '@/components/QuellenMananger.vue'
import ObjekttypenManager from '@/components/ObjekttypenManager.vue'
import PasswordChange from '@/components/PasswordChange.vue'
import SchlagwortManager from '@/components/SchlagwortManager.vue'
import UserLogin from '../components/UserLogin.vue'
import UserManager from '../components/UserManager.vue'
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
      path: '/archivEingang',
      name: 'Archiveingang',
      component: ArchivEingang,
      meta: { requiresAuth: true },
    },
    {
      path: '/archivSuche',
      name: 'Archivsuche',
      component: ArchivSuche,
      meta: { requiresAuth: true },
    },
    {
      path: '/changePassword',
      name: 'Passwort ändern',
      component: PasswordChange,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'Login',
      component: UserLogin,
    },
    {
      path: '/objekttypenManager',
      name: 'Objekttypenmanager',
      component: ObjekttypenManager,
      meta: { requiresAuth: true },
    },
    {
      path: '/quellenManager',
      name: 'Quellenmanager',
      component: QuellenMananger,
      meta: { requiresAuth: true },
    },
    {
      path: '/schlagwortManager',
      name: 'Schlagwortmanager',
      component: SchlagwortManager,
      meta: { requiresAuth: true },
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
