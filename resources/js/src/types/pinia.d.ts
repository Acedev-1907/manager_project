/* eslint-disable @typescript-eslint/no-unused-vars */
import 'pinia';
import { PersistOptions } from '../plugins/piniaPersist';

declare module 'pinia' {
  export interface DefineStoreOptionsBase<_S, _Store> {
    /**
     * Persist store state to localStorage
     */
    persist?: PersistOptions | boolean;
  }
}

