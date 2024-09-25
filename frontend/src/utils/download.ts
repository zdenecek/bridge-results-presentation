
export function downloadTextFile(filename: string, text: string) : void {
    // Create a new Blob object using the text content
    const blob = new Blob([text], { type: 'text/plain' });
   console.log(text) 
    // Create a link element
    const link = document.createElement('a');
    
    // Create a URL for the blob and set it as the href attribute of the link
    link.href = URL.createObjectURL(blob);
    
    // Set the download attribute with the desired file name
    link.download = filename;
    
    // Append the link to the document body (it's required for some browsers)
    document.body.appendChild(link);
    
    // Programmatically click the link to trigger the download
    link.click();
    
    // Remove the link from the document
    document.body.removeChild(link);
}