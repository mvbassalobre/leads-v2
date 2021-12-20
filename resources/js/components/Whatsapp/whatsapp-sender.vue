<template>
    <div>
        <button @click="test">Testar</button>
    </div>
</template>
<script>
import io from "socket.io-client";

export default {
    data() {
        return {
            socket: null,
        };
    },
    created() {
        this.init();
    },
    methods: {
        init() {
            this.socket = io("http://localhost:3000", {
                reconnection: true,
                reconnectionDelay: 500,
                reconnectionAttempts: 10,
            });
            this.socket.on("connect", () => {
                console.log(`connect ${this.socket.id}`);
            });

            this.socket.on("disconnect", () => {
                console.log("disconnect");
            });

            this.socket.on("test-socket", () => {
                console.log("teste");
            });
            console.log("init ...", this.socket);
        },
        test() {
            this.socket.emit("test-connected-user", (data) => {
                console.log(data);
            });
        },
    },
};
</script>
