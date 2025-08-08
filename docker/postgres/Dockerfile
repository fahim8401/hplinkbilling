FROM postgres:13

# Copy initialization scripts
COPY init-scripts/ /docker-entrypoint-initdb.d/

# Set environment variables
ENV POSTGRES_DB=hplinkbilling
ENV POSTGRES_USER=hplink
ENV POSTGRES_PASSWORD=secret

EXPOSE 5432