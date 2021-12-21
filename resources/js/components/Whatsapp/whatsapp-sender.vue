<template>
    <div class="d-flex flex-column">
        <template v-if="!connection.socket_status">Conectando ao socket ...</template>
        <template v-else>
            <div v-if="!action || action == 'logged'">
                <button @click="startOrClose">
                    {{ action ? "Fechar Conexão com Webservice" : "Conectar Webservice" }}
                </button>
            </div>
            <template v-if="action == 'loading'"> Carregando ... </template>
            <template v-if="action == 'show_qr_code'">
                <div>
                    <img class="mt-4" :src="qr_code.base64Qrimg" />
                </div>
            </template>
            <template v-if="action == 'logged'">
                <h1>Usuário Logado</h1>
                <input v-model="message.phone_number" placeholder="Número do telefone" />
                <textarea v-model="message.body" />
                <button @click="sendMessage">Enviar</button>
            </template>
        </template>
    </div>
</template>
<script>
import io from "socket.io-client";

export default {
    data() {
        return {
            connection: {
                id: null,
                socket_status: false,
            },
            socket: null,
            action: null,
            qr_code: {},
            // token: null,
            session_id: "celular-vinicius",
            token: {
                WABrowserId: '"Zf4PxDjUuhJJ6Zlq72+exg=="',
                WASecretBundle:
                    '{"key":"+xYKIvufXJZdpTP75eTLKbBcFfNjjvcNudv5pi8CLYc=","encKey":"rEWvgBaYfkmz0ef5DKYdvQxqvXUD/LQyZHfORYTcwt8=","macKey":"+xYKIvufXJZdpTP75eTLKbBcFfNjjvcNudv5pi8CLYc="}',
                WAToken1: '"SbUJt77PRnilZo4gOuUukJtfvCqmQAGmS9QMIO0CuaQ="',
                WAToken2: '"1@RWaRytdSLRGbr2rMCGp2odPL8RiaQEfSGbLT7RoRSVfTs2r0BNchZrjs/LLpi08mABTXijuIfnbn1g=="',
            },
            message: {
                phone_number: null,
                body: null,
            },
        };
    },
    created() {
        this.init();
        window.addEventListener("beforeunload", () => {
            this.socket.emit("close-connection", { session_id: this.session_id, id: this.connection.id });
            return;
        });
    },
    methods: {
        init() {
            this.socket = io("http://localhost:3000", {
                reconnection: true,
                reconnectionDelay: 500,
                reconnectionAttempts: 10,
            });

            this.socket.on("connected", (data) => {
                console.log(data);
                this.connection.id = data.id;
                this.connection.socket_status = true;
                this.action = null;
            });

            this.socket.on("disconnect", () => {
                this.connection.id = null;
                this.connection.socket_status = false;
                this.action = null;
            });

            this.socket.on("session-conflict", () => {
                alert("Sessão já iniciada");
            });

            this.socket.on("qr-generated", (data) => {
                this.qr_code = data;
                this.action = "show_qr_code";
            });

            this.socket.on("session-updated", (data) => {
                if (data.statusSession == "qrReadSuccess") {
                    this.action = "loading";
                }
            });

            this.socket.on("token-generated", (data) => {
                this.action = "logged";
                this.token = data.token;
            });

            this.socket.on("sent-message", () => {
                console.log("Mensagem enviada !!");
                Object.keys(this.message).map((key) => {
                    this.message[key] = null;
                });
            });

            this.socket.on("message-failed", (er) => {
                console.log(er);
            });
        },
        startOrClose() {
            if (this.action) {
                this.action = null;
                return this.socket.emit("close-connection", { session_id: this.session_id });
            }

            this.action = "loading";

            this.socket.emit("start-engine", { session_id: this.session_id, token: this.token });
        },
        sendMessage() {
            this.socket.emit("send-message", {
                phone_number: this.message.phone_number,
                body: this.message.body,
                session_id: this.session_id,
            });
        },
    },
};
</script>
