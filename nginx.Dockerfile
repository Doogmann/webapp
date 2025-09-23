FROM nginx:1.25-alpine

# Global + server-konfig
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Statiska filer
COPY public/ /var/www/html/public/

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
