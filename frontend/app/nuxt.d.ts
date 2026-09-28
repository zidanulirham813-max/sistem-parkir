import type { AxionsInstance } from "axios";

declare module "#app" {
    interface NuxtApp {
        $api: AxionsInstance;
    }
}

declare module "vue" {
    interface ComponentCustomProperties {
        $api: AxionsInstance;
    }
}

export {};