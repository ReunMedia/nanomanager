import type { EmptyObject, OmitIndexSignature } from "type-fest";

interface Operation {
  /**
   * Input parameters of the operation
   */
  parameters: object;
  /**
   * Operation result
   */
  result: {
    /**
     * Returned data
     */
    data: Record<string, unknown>;
  };
}

interface Operations {
  [key: string]: Operation;
  listFiles: {
    parameters: EmptyObject;
    result: {
      data: {
        /**
         * List of filenames
         */
        files: string[];
      };
    };
  };
}

const apiUrl = "http://localhost:8080";

async function apiRequest<T extends keyof OmitIndexSignature<Operations>>(
  operation: T,
  parameters: Operations[T]["parameters"],
): Promise<Operations[T]["result"]> {
  const response = await fetch(apiUrl, {
    method: "POST",
    body: JSON.stringify({
      operationType: operation,
      parameters,
    }),
  });

  const data: Operations[T]["result"] = await response.json();

  return data;
}

export { apiRequest };
