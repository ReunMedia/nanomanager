import type { PageLoad } from "./$types";

export const load: PageLoad = async ({ fetch }) => {
  const apiUrl = "http://localhost:8080";

  const response = await fetch(
    `${apiUrl}?` +
      new URLSearchParams({
        operation: "listFiles",
      }),
  );
  const data = await response.json();
  return data;
};
