FROM nginx:alpine

RUN apk update && apk upgrade && apk add --no-cache bash

COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]