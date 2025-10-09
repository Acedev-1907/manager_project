import 'pinia';
import { PersistOptions } from '../plugins/piniaPersist';

declare module 'pinia' {
  export interface DefineStoreOptionsBase<S, Store> {
    /**
     * Persist store state to localStorage
     */
    persist?: PersistOptions | boolean;
  }
}

