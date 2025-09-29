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
  renameFile: {
    parameters: {
      oldName: string;
      newName: string;
    };
    result: {
      data: {
        /**
         * New name of the file. If the renaming failed, the original file name
         * is returned.
         */
        newName: string;
      };
    };
  };
  deleteFile: {
    parameters: {
      filename: string;
    };
    result: {
      data: {
        success: boolean;
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
