FROM alpine:latest

WORKDIR /app

ARG FRP_VERSION=0.53.2
ADD https://github.com/fatedier/frp/releases/download/v${FRP_VERSION}/frp_${FRP_VERSION}_linux_amd64.tar.gz /app/frp.tar.gz
RUN tar -zxvf frp.tar.gz --strip-components=1 && \
    rm frp.tar.gz

CMD ["./frps", "-c", "/app/frps.toml"]