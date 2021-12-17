<template>
    <div>
        <h1>Wpp - Sender</h1>
    </div>
</template>
<script>
// import Venom from "venom-bot";

export default {
    data() {
        return {
            webservice: "http://localhost:3000/api",
            session: {
                WABrowserId: '"OlpPuvJHHK6YDwl8d1g1lg=="',
                WASecretBundle:
                    '{"key":"rJSDK3Sg/2GlQkd+gwIY0+KAUYJy4wxf8H0SdXIiVoY=","encKey":"DI18+e6IN8Epd72YbzJWDkJTXsM5E7nm0wyknIetOJ4=","macKey":"rJSDK3Sg/2GlQkd+gwIY0+KAUYJy4wxf8H0SdXIiVoY="}',
                WAToken1: '"yAuNc02EP4uyi7/GOSxybDGfJe5wSIE1WbBScp2C9jI="',
                WAToken2: '"1@KMQsUZWae6N3uPPBGIwdSqKWPLnFV8zeOiU+gaM5inJDi9+5eczguQaQO0qT2K9xhiDa5n8GhH9HpA=="',
            },
            phone_number: "5514996766177@c.us",
            buttons: [
                {
                    buttonText: {
                        displayText: "Opção 1",
                    },
                },
                {
                    buttonText: {
                        displayText: "Opção 2",
                    },
                },
            ],
        };
    },
    created() {
        this.initSender();
    },
    methods: {
        initSender() {
            console.log("venom");
        },
    },
};
</script>
