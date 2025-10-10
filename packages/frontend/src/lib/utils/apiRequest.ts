import type {
  operation_deleteFile,
  operation_listFiles,
  operation_renameFile,
  operation_uploadFile,
  OperationType,
} from "../../types/api";

/**
 * URL is set by Nanomanager backend in production
 */
const apiUrl = import.meta.env.DEV
  ? "http://localhost:8080"
  : "%NANOMANAGER_API_URL%";

type Operations = Pick<
  {
    listFiles: operation_listFiles;
    renameFile: operation_renameFile;
    deleteFile: operation_deleteFile;
    uploadFile: operation_uploadFile;
  },
  OperationType
>;

async function apiRequest<T extends keyof Operations>(
  operation: T,
  parameters: Operations[T]["parameters"],
): Promise<Operations[T]["result"]> {
  let body: string | FormData = "";

  // Handle file uploads as `multipart/form-data`
  if (operation === "uploadFile") {
    body = new FormData();

    body.append("operationType", operation);

    for (const file of (parameters as operation_uploadFile["parameters"])
      .files) {
      body.append("files[]", file);
    }
  }
  // All other requests are sent as JSON
  else {
    body = JSON.stringify({
      operationType: operation,
      parameters,
    });
  }

  const response = await fetch(apiUrl, {
    method: "POST",
    body,
  });

  const data: Operations[T]["result"] = await response.json();

  return data;
}

export { apiRequest };
