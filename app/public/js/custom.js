function uploadFile(file, uploadId, errorId) {
	if (file) {
		console.log("Uploading file...");
		const reader = new FileReader();
		reader.onload = () => {
			console.info("File converted to base64");
			const base64 = reader.result;
			console.debug("File content: " + base64);
			document.getElementById(uploadId).value = base64;
		};
		reader.onerror = (error) => {
			console.error("File upload failed: " + error);
			document.getElementById(errorId).value += error;
		};
		reader.readAsDataURL(file);
	}
}
