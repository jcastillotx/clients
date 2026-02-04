/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          primary: '#5F5F82',    // Your brand purple
          secondary: '#C8D7EA',  // Your brand light blue
          cta: '#5F5F82',        // Primary for CTA
          background: '#F8FAFC', // Light slate background
          text: '#1E293B',       // Dark slate text
          muted: '#64748B',      // Slate-500
          accent: '#A8B3C8',     // Complementary mid-blue
        },
        slate: {
          750: '#334155', // Custom slate-750 color
        }
      },
      spacing: {
        '0.5': '0.125rem', // 2px
      },
      fontFamily: {
        heading: ['Poppins', 'sans-serif'],
        body: ['Open Sans', 'sans-serif'],
        sans: ['Open Sans', 'sans-serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.2s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        }
      }
    },
  },
  plugins: [],
}
