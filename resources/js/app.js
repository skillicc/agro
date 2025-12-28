import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Vuetify
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

// Get saved theme or default to light
const savedTheme = localStorage.getItem('theme') || 'light'

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: savedTheme,
        themes: {
            light: {
                colors: {
                    primary: '#4CAF50',
                    secondary: '#8BC34A',
                    accent: '#CDDC39',
                    error: '#f44336',
                    warning: '#ff9800',
                    info: '#2196F3',
                    success: '#4CAF50',
                },
            },
            dark: {
                colors: {
                    primary: '#66BB6A',
                    secondary: '#9CCC65',
                    accent: '#D4E157',
                    error: '#EF5350',
                    warning: '#FFA726',
                    info: '#42A5F5',
                    success: '#66BB6A',
                },
            },
        },
    },
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

app.mount('#app')
